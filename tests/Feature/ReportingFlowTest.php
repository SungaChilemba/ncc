<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ReportEvidence;
use App\Support\ReportStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function validComplaint(array $overrides = []): array
    {
        return array_merge([
            'child_age' => 12,
            'child_name' => 'Test Child',
            'district' => 'Lilongwe',
            'village' => 'Area 25',
            'child_gender' => 'Female',
            'violation_type' => 'Neglect',
            'incident_date' => now()->subWeek()->toDateString(),
            'description' => 'A detailed and sensitive account used for the reporting flow test.',
            'reporter_name' => 'Test Reporter',
            'reporter_contact' => '+265 999 000 000',
            'consent_given' => '1',
        ], $overrides);
    }

    public function test_reporter_submission_creates_encrypted_case_with_reference_and_pin(): void
    {
        $response = $this->post(route('reporting.store'), $this->validComplaint([
            'urgent' => '1',
        ]));

        $response
            ->assertRedirect(route('reporting', ['tab' => 'complaint']))
            ->assertSessionHas('report_reference')
            ->assertSessionHas('report_pin');

        $reference = session('report_reference');
        $pin = session('report_pin');

        $this->assertMatchesRegularExpression('/^NCC-\d{4}-[0-9A-HJKMNP-TV-Z]{6}$/', $reference);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $pin);

        $report = Report::where('reference_number', $reference)->firstOrFail();

        $this->assertSame('received', $report->status);
        $this->assertSame('urgent', $report->priority);
        $this->assertSame('Test Child', $report->child_name);
        $this->assertSame('Test Reporter', $report->reporter_name);
        $this->assertSame('+265 999 000 000', $report->reporter_contact);
        $this->assertNotNull($report->reporter_contact_hash);
        $this->assertTrue($report->consent_given);
        $this->assertTrue($report->verifyPin($pin));

        // PII must be ciphertext at rest, not merely hidden from the model.
        $raw = DB::table('reports')->where('id', $report->id)->first();
        $this->assertNotSame('Test Child', $raw->child_name);
        $this->assertNotSame('Test Reporter', $raw->reporter_name);
        $this->assertNotSame('+265 999 000 000', $raw->reporter_contact);
        $this->assertNotSame($pin, $raw->access_pin_hash);

        $this->assertDatabaseHas('report_events', [
            'report_id' => $report->id,
            'type' => 'received',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('report_access_logs', [
            'report_id' => $report->id,
            'action' => 'submitted_by_reporter',
        ]);
    }

    public function test_anonymous_reporter_can_still_leave_contact_details(): void
    {
        $this->post(route('reporting.store'), $this->validComplaint([
            'reporter_name' => '',
            'is_anonymous' => '1',
        ]))->assertSessionHas('report_reference');

        $report = Report::where('reference_number', session('report_reference'))->firstOrFail();

        $this->assertTrue($report->is_anonymous);
        $this->assertSame('+265 999 000 000', $report->reporter_contact);
    }

    public function test_tracking_requires_the_correct_pin_and_locks_after_repeated_failures(): void
    {
        $report = Report::factory()->create([
            'access_pin_hash' => Hash::make('123456'),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
        ]);

        $this->post(route('reporting.track'), [
            'reference_number' => $report->reference_number,
            'pin' => '000000',
        ])->assertSessionHasErrors('pin');

        $this->assertSame(1, $report->fresh()->pin_attempts);

        foreach (range(2, 5) as $attempt) {
            $this->post(route('reporting.track'), [
                'reference_number' => $report->reference_number,
                'pin' => '000000',
            ])->assertSessionHasErrors('pin');
        }

        $report->refresh();

        $this->assertSame(5, $report->pin_attempts);
        $this->assertNotNull($report->pin_locked_until);
        $this->assertTrue($report->pin_locked_until->isFuture());

        // Even the correct PIN is rejected while the case is locked.
        $this->post(route('reporting.track'), [
            'reference_number' => $report->reference_number,
            'pin' => '123456',
        ])->assertSessionHasErrors('pin');
    }

    public function test_correct_pin_grants_tracking_access(): void
    {
        $report = Report::factory()->create([
            'access_pin_hash' => Hash::make('123456'),
            'status' => 'investigation',
        ]);

        $this->post(route('reporting.track'), [
            'reference_number' => $report->reference_number,
            'pin' => '123456',
        ])->assertRedirect(route('reporting', ['tab' => 'track']))
            ->assertSessionHas('tracking_status');

        $this->get(route('reporting', ['tab' => 'track']))
            ->assertOk()
            ->assertSee($report->reference_number)
            ->assertSee('Investigation ongoing');
    }

    public function test_evidence_is_stored_privately_and_only_downloadable_with_a_grant(): void
    {
        Storage::fake('report_evidence');

        $report = Report::factory()->create([
            'access_pin_hash' => Hash::make('123456'),
        ]);

        $this->post(route('reporting.evidence.store'), [
            'reference_number' => $report->reference_number,
            'pin' => '123456',
            'evidence' => [
                UploadedFile::fake()->create('evidence.pdf', 128, 'application/pdf'),
            ],
        ])->assertRedirect(route('reporting', ['tab' => 'evidence']))
            ->assertSessionHas('evidence_status');

        $evidence = ReportEvidence::where('report_id', $report->id)->firstOrFail();

        $this->assertSame('report_evidence', $evidence->disk);
        $this->assertSame('evidence.pdf', $evidence->original_name);
        $this->assertSame('not_scanned', $evidence->scan_status);
        Storage::disk('report_evidence')->assertExists($evidence->path);

        // No public URL and no grant means no access.
        $this->flushSession();
        $this->get(route('reporting.evidence.download', $evidence))->assertForbidden();

        $this->post(route('reporting.track'), [
            'reference_number' => $report->reference_number,
            'pin' => '123456',
        ])->assertRedirect();

        $this->get(route('reporting.evidence.download', $evidence))
            ->assertOk()
            ->assertDownload('evidence.pdf');

        $this->assertDatabaseHas('report_access_logs', [
            'report_id' => $report->id,
            'action' => 'evidence_downloaded_by_reporter',
        ]);
    }

    public function test_a_verified_session_can_upload_more_evidence_without_resending_the_pin(): void
    {
        Storage::fake('report_evidence');

        $report = Report::factory()->create([
            'access_pin_hash' => Hash::make('123456'),
        ]);

        $this->post(route('reporting.track'), [
            'reference_number' => $report->reference_number,
            'pin' => '123456',
        ])->assertRedirect();

        $this->post(route('reporting.evidence.store'), [
            'reference_number' => $report->reference_number,
            'evidence' => [
                UploadedFile::fake()->create('second-evidence.pdf', 64, 'application/pdf'),
            ],
        ])->assertRedirect(route('reporting', ['tab' => 'evidence']))
            ->assertSessionHas('evidence_status');

        $this->assertSame(1, $report->evidence()->count());
    }

    public function test_public_statistics_suppress_small_cells_and_stay_national(): void
    {
        config()->set('reporting.stats_min_cell', 5);

        $year = now()->year;

        Report::factory()->count(3)->create([
            'violation_type' => 'Neglect',
            'received_at' => now(),
        ]);

        $stats = app(ReportStats::class)->forYear($year);

        $this->assertNull($stats['total']);
        $this->assertNull($stats['by_violation']['Neglect'] ?? null);

        Report::factory()->count(2)->create([
            'violation_type' => 'Neglect',
            'received_at' => now(),
        ]);

        $stats = app(ReportStats::class)->forYear($year);

        $this->assertSame(5, $stats['total']);
        $this->assertSame(5, $stats['by_violation']['Neglect']);
        $this->assertArrayNotHasKey('district', $stats);
        $this->assertArrayNotHasKey('village', $stats);
        $this->assertArrayNotHasKey('reference_number', $stats);
    }

    public function test_statistics_page_renders_national_aggregates_for_sufficient_groups(): void
    {
        config()->set('reporting.stats_min_cell', 2);

        Report::factory()->count(2)->create([
            'violation_type' => 'Neglect',
            'received_at' => now(),
        ]);

        $this->get(route('reporting', ['tab' => 'stats']))
            ->assertOk()
            ->assertSee('Case Statistics')
            ->assertSee('Cases filed')
            ->assertSee('Neglect');
    }

    public function test_evidence_validation_failure_keeps_the_evidence_tab_open(): void
    {
        $this->post(route('reporting.evidence.store'), [])
            ->assertSessionHasErrors(['reference_number', 'evidence'])
            ->assertSessionHas('report_tab', 'evidence');
    }
}

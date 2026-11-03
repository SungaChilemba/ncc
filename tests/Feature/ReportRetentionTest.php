<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\ReportNote;
use App\Support\ReportRetention;
use App\Support\ReportStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function expiredReport(array $overrides = []): Report
    {
        return Report::factory()->closed()->create(array_merge([
            'received_at' => now(),
            'retention_delete_at' => now()->subDay(),
            'child_name' => 'Retention Child',
            'description' => 'Sensitive narrative that must be removed.',
            'reporter_name' => 'Retention Reporter',
            'reporter_contact' => '+265 999 000 111',
            'village' => 'Sensitive Village',
        ], $overrides));
    }

    private function attachEvidence(Report $report, string $disk = 'report_evidence'): ReportEvidence
    {
        $path = 'reports/'.$report->id.'/evidence.pdf';

        Storage::disk($disk)->put($path, 'evidence');

        return ReportEvidence::create([
            'report_id' => $report->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => 'retention-evidence.pdf',
            'mime_type' => 'application/pdf',
            'size' => 8,
            'scan_status' => 'not_scanned',
            'uploaded_at' => now(),
        ]);
    }

    public function test_expired_cases_are_anonymised_and_evidence_and_notes_are_purged(): void
    {
        Storage::fake('report_evidence');
        config()->set('reporting.stats_min_cell', 1);

        $report = $this->expiredReport();
        $evidence = $this->attachEvidence($report);
        $note = ReportNote::create([
            'report_id' => $report->id,
            'user_id' => null,
            'body' => 'Sensitive handover note.',
        ]);

        $this->artisan('reports:purge-expired')->assertSuccessful();

        $report->refresh();

        $this->assertNotNull($report->anonymised_at);
        $this->assertNull($report->child_name);
        $this->assertNull($report->reporter_name);
        $this->assertNull($report->reporter_contact);
        $this->assertNull($report->reporter_contact_hash);
        $this->assertNull($report->village);
        $this->assertSame(ReportRetention::REDACTED_NARRATIVE, $report->description);
        $this->assertFalse($report->verifyPin('123456'));

        $this->assertDatabaseMissing('report_evidence', ['id' => $evidence->id]);
        $this->assertDatabaseMissing('report_notes', ['id' => $note->id]);
        Storage::disk('report_evidence')->assertMissing($evidence->path);

        $this->assertDatabaseHas('report_access_logs', [
            'report_id' => $report->id,
            'action' => 'anonymised',
            'reason' => 'Retention period expired',
        ]);

        // The de-identified record still contributes to national statistics.
        $stats = app(ReportStats::class)->forYear(now()->year);
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['by_violation'][$report->violation_type]);
    }

    public function test_legal_hold_blocks_retention_purge(): void
    {
        Storage::fake('report_evidence');

        $report = $this->expiredReport(['legal_hold' => true]);
        $evidence = $this->attachEvidence($report);

        $this->artisan('reports:purge-expired')->assertSuccessful();

        $report->refresh();

        $this->assertNull($report->anonymised_at);
        $this->assertSame('Retention Child', $report->child_name);
        $this->assertSame('Sensitive narrative that must be removed.', $report->description);
        $this->assertDatabaseHas('report_evidence', ['id' => $evidence->id]);
        Storage::disk('report_evidence')->assertExists($evidence->path);
    }

    public function test_dry_run_reports_due_cases_without_changing_them(): void
    {
        $report = $this->expiredReport();

        $this->artisan('reports:purge-expired', ['--dry-run' => true])
            ->expectsOutputToContain('1 case(s) are due')
            ->assertSuccessful();

        $this->assertNull($report->fresh()->anonymised_at);
        $this->assertSame('Retention Child', $report->fresh()->child_name);
    }

    public function test_erasure_command_requires_a_reason_and_respects_legal_hold(): void
    {
        Storage::fake('report_evidence');

        $report = $this->expiredReport(['retention_delete_at' => null]);
        $evidence = $this->attachEvidence($report);

        $this->artisan('reports:erase', ['reference' => $report->reference_number, '--force' => true])
            ->assertExitCode(1);

        $this->assertNull($report->fresh()->anonymised_at);

        $held = $this->expiredReport([
            'retention_delete_at' => null,
            'legal_hold' => true,
            'child_name' => 'Protected Child',
        ]);

        $this->artisan('reports:erase', [
            'reference' => $held->reference_number,
            '--reason' => 'Subject request',
            '--force' => true,
        ])->assertExitCode(1);

        $this->assertNull($held->fresh()->anonymised_at);
        $this->assertSame('Protected Child', $held->fresh()->child_name);
        $this->assertDatabaseHas('report_evidence', ['id' => $evidence->id]);
    }

    public function test_erasure_command_anonymises_a_case_immediately(): void
    {
        Storage::fake('report_evidence');

        $report = $this->expiredReport(['retention_delete_at' => null]);
        $evidence = $this->attachEvidence($report);

        $this->artisan('reports:erase', [
            'reference' => $report->reference_number,
            '--reason' => 'Subject erasure request',
            '--force' => true,
        ])->assertSuccessful();

        $report->refresh();

        $this->assertNotNull($report->anonymised_at);
        $this->assertNull($report->child_name);
        $this->assertSame(ReportRetention::REDACTED_NARRATIVE, $report->description);
        $this->assertDatabaseMissing('report_evidence', ['id' => $evidence->id]);
        $this->assertDatabaseHas('report_access_logs', [
            'report_id' => $report->id,
            'action' => 'anonymised',
            'reason' => 'Erasure request: Subject erasure request',
        ]);
    }
}

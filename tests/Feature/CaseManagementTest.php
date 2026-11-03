<?php

namespace Tests\Feature;

use App\Http\Middleware\CaseTotpMiddleware;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\ReportNote;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'JBSWY3DPEHPK3PXP';

    private function caseUser(string $role, array $overrides = []): User
    {
        return $this->userWithRole('case_'.$role, $overrides);
    }

    private function confirmTotp(User $user, ?string $secret = null): User
    {
        $user->forceFill([
            'two_factor_secret' => $secret ?? self::SECRET,
            'two_factor_recovery_codes' => ['ABCD1234', 'EFGH5678'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    private function passTotpChallenge(): void
    {
        $this->withSession([
            CaseTotpMiddleware::SESSION_KEY => now()->getTimestamp(),
        ]);
    }

    public function test_case_only_staff_can_log_in_and_are_sent_to_the_case_queue(): void
    {
        $user = $this->caseUser('manager');

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.cases.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_without_cms_or_case_roles_cannot_log_in(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_case_staff_must_confirm_totp_before_opening_cases(): void
    {
        $user = $this->caseUser('manager');

        $this->actingAs($user)
            ->get(route('admin.cases.index'))
            ->assertRedirect(route('admin.cases.totp.setup'));

        $this->get(route('admin.cases.totp.setup'))
            ->assertOk()
            ->assertSee('Add the account');

        $secret = session('case.totp_pending_secret');

        $this->assertIsString($secret);
        $this->assertNotEmpty($secret);

        $this->post(route('admin.cases.totp.confirm'), [
            'code' => Totp::code($secret),
        ])->assertRedirect(route('admin.dashboard'));

        $user->refresh();

        $this->assertTrue($user->hasConfirmedTwoFactor());
        $this->assertSame($secret, $user->two_factor_secret);
        $this->assertCount(8, $user->two_factor_recovery_codes);

        $this->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee('Case Queue');
    }

    public function test_confirmed_staff_must_pass_a_challenge_on_a_fresh_session(): void
    {
        $user = $this->confirmTotp($this->caseUser('manager'));

        $this->actingAs($user)
            ->get(route('admin.cases.index'))
            ->assertRedirect(route('admin.cases.totp.challenge'));

        $this->post(route('admin.cases.totp.verify'), [
            'code' => 'not-a-code',
        ])->assertSessionHasErrors('code');

        $this->post(route('admin.cases.totp.verify'), [
            'code' => Totp::code(self::SECRET),
        ])->assertRedirect(route('admin.cases.index'));

        $this->get(route('admin.cases.index'))->assertOk();
    }

    public function test_a_recovery_code_can_be_used_once(): void
    {
        $user = $this->confirmTotp($this->caseUser('officer'));

        $this->actingAs($user)
            ->get(route('admin.cases.index'))
            ->assertRedirect(route('admin.cases.totp.challenge'));

        $this->post(route('admin.cases.totp.verify'), [
            'code' => 'ABCD-1234',
        ])->assertRedirect(route('admin.cases.index'));

        $user->refresh();

        $this->assertSame(['EFGH5678'], $user->two_factor_recovery_codes);

        $this->flushSession();

        $this->actingAs($user)
            ->get(route('admin.cases.index'))
            ->assertRedirect(route('admin.cases.totp.challenge'));

        $this->post(route('admin.cases.totp.verify'), [
            'code' => 'ABCD1234',
        ])->assertSessionHasErrors('code');
    }

    public function test_cms_and_case_areas_are_separated_by_role(): void
    {
        $cmsAdmin = $this->userWithRole('cms_admin');

        $this->actingAs($cmsAdmin)
            ->get(route('admin.cases.index'))
            ->assertForbidden();

        $caseManager = $this->confirmTotp($this->caseUser('manager'));

        $this->actingAs($caseManager);
        $this->passTotpChallenge();

        $this->get(route('admin.cases.index'))->assertOk();

        $this->get(route('admin.contents.index', ['section' => 'home']))
            ->assertForbidden();

        $this->get(route('admin.media.index'))->assertForbidden();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.cases.index'));
    }

    public function test_officers_only_see_and_open_cases_assigned_to_them(): void
    {
        $officer = $this->confirmTotp($this->caseUser('officer'));

        $assigned = Report::factory()->create(['assigned_to' => $officer->id]);
        $other = Report::factory()->create(['assigned_to' => null]);

        $this->actingAs($officer);
        $this->passTotpChallenge();

        $this->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee($assigned->reference_number)
            ->assertDontSee($other->reference_number);

        $this->get(route('admin.cases.show', $assigned))->assertOk();
        $this->get(route('admin.cases.show', $other))->assertForbidden();
    }

    public function test_manager_can_assign_change_status_add_notes_and_the_actions_are_audited(): void
    {
        $manager = $this->confirmTotp($this->caseUser('manager'));
        $officer = $this->caseUser('officer');
        $report = Report::factory()->create(['status' => 'received']);

        $this->actingAs($manager);
        $this->passTotpChallenge();

        $this->post(route('admin.cases.assign', $report), [
            'assigned_to' => $officer->id,
        ])->assertRedirect();

        $this->assertSame($officer->id, $report->fresh()->assigned_to);
        $this->assertDatabaseHas('report_events', [
            'report_id' => $report->id,
            'type' => 'assigned',
            'is_public' => false,
        ]);

        $this->post(route('admin.cases.status', $report), [
            'status' => 'triage',
            'note' => 'Initial triage call completed.',
        ])->assertRedirect();

        $report->refresh();

        $this->assertSame('triage', $report->status);
        $this->assertDatabaseHas('report_events', [
            'report_id' => $report->id,
            'from_status' => 'received',
            'to_status' => 'triage',
            'is_public' => true,
        ]);

        $this->post(route('admin.cases.notes.store', $report), [
            'body' => 'Sensitive internal handover note.',
        ])->assertRedirect();

        $note = ReportNote::where('report_id', $report->id)->latest('id')->firstOrFail();

        $raw = DB::table('report_notes')->where('id', $note->id)->first();
        $this->assertNotSame('Sensitive internal handover note.', $raw->body);

        $this->get(route('admin.cases.show', $report))
            ->assertOk()
            ->assertSee($officer->name)
            ->assertSee('Sensitive internal handover note.');

        foreach (['assigned', 'status_changed', 'note_added', 'viewed', 'identity_viewed'] as $action) {
            $this->assertDatabaseHas('report_access_logs', [
                'report_id' => $report->id,
                'user_id' => $manager->id,
                'action' => $action,
            ]);
        }
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $manager = $this->confirmTotp($this->caseUser('manager'));
        $report = Report::factory()->create(['status' => 'received']);

        $this->actingAs($manager);
        $this->passTotpChallenge();

        $this->post(route('admin.cases.status', $report), [
            'status' => 'resolved',
        ])->assertForbidden();

        $this->assertSame('received', $report->fresh()->status);
    }

    public function test_intake_can_only_triage_new_cases(): void
    {
        $intake = $this->confirmTotp($this->caseUser('intake'));
        $newCase = Report::factory()->create(['status' => 'received']);
        $activeCase = Report::factory()->create(['status' => 'investigation']);

        $this->actingAs($intake);
        $this->passTotpChallenge();

        $this->post(route('admin.cases.status', $newCase), [
            'status' => 'triage',
        ])->assertRedirect();

        $this->assertSame('triage', $newCase->fresh()->status);

        $this->post(route('admin.cases.status', $activeCase), [
            'status' => 'closed',
        ])->assertForbidden();

        $this->assertSame('investigation', $activeCase->fresh()->status);
    }

    public function test_auditors_see_a_redacted_case_and_audit_trail_but_cannot_act(): void
    {
        Storage::fake('report_evidence');

        $auditor = $this->confirmTotp($this->caseUser('auditor'));
        $report = Report::factory()->create([
            'child_name' => 'Secret Child',
            'description' => 'Secret narrative that must stay hidden.',
            'reporter_contact' => '+265 999 111 222',
        ]);

        ReportNote::create([
            'report_id' => $report->id,
            'user_id' => null,
            'body' => 'Secret internal note.',
        ]);

        $evidence = ReportEvidence::create([
            'report_id' => $report->id,
            'disk' => 'report_evidence',
            'path' => 'reports/'.$report->id.'/evidence.pdf',
            'original_name' => 'secret-evidence.pdf',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'scan_status' => 'not_scanned',
            'uploaded_at' => now(),
        ]);

        Storage::disk('report_evidence')->put($evidence->path, 'file');

        $this->actingAs($auditor);
        $this->passTotpChallenge();

        $this->get(route('admin.cases.show', $report))
            ->assertOk()
            ->assertSee('Identity-redacted view')
            ->assertSee($report->reference_number)
            ->assertDontSee('Secret Child')
            ->assertDontSee('Secret narrative')
            ->assertDontSee('Secret internal note')
            ->assertDontSee('secret-evidence.pdf');

        $this->get(route('admin.cases.audit', $report))->assertOk();

        $this->assertDatabaseHas('report_access_logs', [
            'report_id' => $report->id,
            'user_id' => $auditor->id,
            'action' => 'audit_viewed',
        ]);

        $this->assertDatabaseMissing('report_access_logs', [
            'report_id' => $report->id,
            'user_id' => $auditor->id,
            'action' => 'identity_viewed',
        ]);

        $this->get(route('admin.cases.evidence.download', [$report, $evidence]))
            ->assertForbidden();

        $this->post(route('admin.cases.notes.store', $report), [
            'body' => 'Attempted write.',
        ])->assertForbidden();

        $this->post(route('admin.cases.status', $report), [
            'status' => 'closed',
        ])->assertForbidden();

        $this->post(route('admin.cases.assign', $report), [
            'assigned_to' => null,
        ])->assertForbidden();
    }

    public function test_staff_evidence_is_private_and_download_requires_case_access(): void
    {
        Storage::fake('report_evidence');

        $manager = $this->confirmTotp($this->caseUser('manager'));
        $otherOfficer = $this->confirmTotp($this->caseUser('officer'));
        $report = Report::factory()->create();

        $this->actingAs($manager);
        $this->passTotpChallenge();

        $this->post(route('admin.cases.evidence.store', $report), [
            'evidence' => [
                UploadedFile::fake()->create('case-file.pdf', 64, 'application/pdf'),
            ],
        ])->assertRedirect();

        $evidence = ReportEvidence::where('report_id', $report->id)->firstOrFail();

        $this->assertSame('report_evidence', $evidence->disk);
        $this->assertSame($manager->id, $evidence->uploaded_by);
        $this->assertSame('case-file.pdf', $evidence->original_name);
        $this->assertSame('not_scanned', $evidence->scan_status);
        Storage::disk('report_evidence')->assertExists($evidence->path);

        $this->assertDatabaseHas('report_access_logs', [
            'report_id' => $report->id,
            'action' => 'evidence_uploaded',
        ]);

        $this->get(route('admin.cases.evidence.download', [$report, $evidence]))
            ->assertOk()
            ->assertDownload('case-file.pdf');

        $this->flushSession();
        $this->actingAs($otherOfficer);
        $this->passTotpChallenge();

        $this->get(route('admin.cases.evidence.download', [$report, $evidence]))
            ->assertForbidden();
    }

    public function test_guests_cannot_reach_case_routes(): void
    {
        $report = Report::factory()->create();

        $this->get(route('admin.cases.index'))->assertForbidden();
        $this->get(route('admin.cases.show', $report))->assertForbidden();
        $this->post(route('admin.cases.status', $report), ['status' => 'triage'])->assertForbidden();
    }
}

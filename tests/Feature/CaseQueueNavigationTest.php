<?php

namespace Tests\Feature;

use App\Http\Middleware\CaseTotpMiddleware;
use App\Models\Report;
use App\Models\User;
use App\Support\CaseQueues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseQueueNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function confirmTotp(User $user): User
    {
        $user->forceFill([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
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

    public function test_manager_sees_every_status_queue_and_shortcut(): void
    {
        $queues = CaseQueues::forUser($this->userWithRole('case_manager'));

        $this->assertSame('All cases', $queues['primary']['label']);
        $this->assertSame(['unassigned', 'urgent', 'mine'], array_column($queues['shortcuts'], 'key'));
        $this->assertSame(
            ['received', 'triage', 'investigation', 'referred'],
            array_column($queues['groups'][0]['items'], 'key'),
        );
        $this->assertSame(
            ['resolved', 'closed', 'duplicate', 'withdrawn'],
            array_column($queues['groups'][1]['items'], 'key'),
        );
    }

    public function test_intake_officer_sees_assignment_shortcuts(): void
    {
        $queues = CaseQueues::forUser($this->userWithRole('case_intake'));

        $this->assertSame('All cases', $queues['primary']['label']);
        $this->assertSame(['unassigned', 'urgent'], array_column($queues['shortcuts'], 'key'));
    }

    public function test_case_officer_gets_my_cases_and_only_the_urgent_shortcut(): void
    {
        $queues = CaseQueues::forUser($this->userWithRole('case_officer'));

        $this->assertSame('My cases', $queues['primary']['label']);
        $this->assertSame(['urgent'], array_column($queues['shortcuts'], 'key'));
    }

    public function test_auditor_gets_status_queues_without_work_shortcuts(): void
    {
        $queues = CaseQueues::forUser($this->userWithRole('case_auditor'));

        $this->assertSame([], $queues['shortcuts']);
        $this->assertCount(2, $queues['groups']);
    }

    public function test_officer_counts_only_include_cases_assigned_to_them(): void
    {
        $officer = $this->userWithRole('case_officer');
        $other = $this->userWithRole('case_officer');

        Report::factory()->assignedTo($officer)->create(['status' => 'received']);
        Report::factory()->assignedTo($officer)->create(['status' => 'investigation']);
        Report::factory()->assignedTo($other)->create(['status' => 'received']);
        Report::factory()->create(['status' => 'received']);

        $queues = CaseQueues::forUser($officer);

        $this->assertSame(2, $queues['primary']['count']);
        $this->assertSame(1, $queues['groups'][0]['items'][0]['count']); // received
        $this->assertSame(0, $queues['groups'][0]['items'][1]['count']); // under assessment
        $this->assertSame(1, $queues['groups'][0]['items'][2]['count']); // investigation ongoing
    }

    public function test_sidebar_renders_manager_shortcuts_in_order(): void
    {
        $user = $this->confirmTotp($this->userWithRole('case_manager'));

        $this->passTotpChallenge();

        $this->actingAs($user)
            ->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Case Queues',
                'All cases',
                'Active',
                'Received',
                'Under assessment',
                'Investigation ongoing',
                'Referred for support',
                'Closed & resolved',
                'Resolved',
                'Duplicate',
                'Withdrawn',
                'Shortcuts',
                'Unassigned',
                'Urgent',
                'Assigned to me',
                'Case Queue',
            ]);
    }

    public function test_totp_setup_page_does_not_expose_case_queue_counts(): void
    {
        $user = $this->userWithRole('case_manager');

        $this->actingAs($user)
            ->get(route('admin.cases.totp.setup'))
            ->assertOk()
            ->assertDontSee('Case Queues');
    }

    public function test_sidebar_renders_queue_navigation_for_case_staff(): void
    {
        $user = $this->confirmTotp($this->userWithRole('case_officer'));

        Report::factory()->assignedTo($user)->create(['status' => 'received']);

        $this->passTotpChallenge();

        $this->actingAs($user)
            ->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee('Case Queues')
            ->assertSee('My cases')
            ->assertSee('Under assessment')
            ->assertSee('Urgent')
            ->assertSee('Shortcuts')
            ->assertSee('aria-current="page"', false);
    }
}

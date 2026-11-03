<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Case authorisation is driven by permissions from the user's roles plus the
 * widest case scope among those roles.
 *
 * Built-in roles reproduce the previous matrix. Administrators can create new
 * roles that combine these permissions without changing code, while the case
 * status graph and retention rules remain domain logic.
 */
class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('cases.view');
    }

    public function view(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.view')
            && $user->caseScopeAllows($report);
    }

    public function assign(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.assign')
            && $user->caseScopeAllows($report);
    }

    public function changeStatus(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.status.change')
            && $user->caseScopeAllows($report);
    }

    /**
     * Check the requested transition, not just whether the user may change
     * status at all. The target must be allowed by the status graph and by a
     * per-transition permission.
     */
    public function transition(User $user, Report $report, string $target): bool
    {
        return $this->changeStatus($user, $report)
            && $report->canTransitionTo($target)
            && $user->hasPermission("cases.transition.{$target}");
    }

    public function addNote(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.notes.add')
            && $user->caseScopeAllows($report);
    }

    public function uploadEvidence(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.evidence.upload')
            && $user->caseScopeAllows($report);
    }

    public function downloadEvidence(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.evidence.download')
            && $user->caseScopeAllows($report);
    }

    /**
     * Roles without this permission receive the redacted auditor-style view.
     */
    public function viewIdentity(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.identity.view')
            && $user->caseScopeAllows($report);
    }

    public function viewAudit(User $user, Report $report): bool
    {
        return $user->hasPermission('cases.audit.view')
            && $user->caseScopeAllows($report);
    }
}

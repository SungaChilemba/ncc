<?php

namespace App\Support;

use App\Models\Report;
use App\Models\User;
use Closure;

/**
 * Builds the case-queue navigation for a user from their case scope and
 * capabilities.
 *
 * Status queues are available to anyone who can view cases. Shortcuts are only
 * shown when the user can actually act on them, so officers and auditors get a
 * lighter sidebar than intake staff and managers.
 */
class CaseQueues
{
    /** @var list<string> */
    public const ACTIVE_STATUSES = ['received', 'triage', 'investigation', 'referred'];

    /** @var list<string> */
    public const CLOSED_STATUSES = ['resolved', 'closed', 'duplicate', 'withdrawn'];

    /**
     * @return array{
     *     primary: array{key: string, label: string, params: array<string, string>, count: int},
     *     groups: list<array{key: string, label: string, items: list<array{key: string, label: string, params: array<string, string>, count: int, dot: string}>}>,
     *     shortcuts: list<array{key: string, label: string, params: array<string, string>, count: int, icon: string}>
     * }
     */
    public static function forUser(User $user): array
    {
        $scope = $user->caseScope();
        $assignedScope = $scope === 'assigned';
        $canAssign = $user->hasPermission('cases.assign');
        $canChangeStatus = $user->hasPermission('cases.status.change');
        $canBeAssigned = $user->canBeAssigned();
        $counts = self::statusCounts($user);

        $buildStatuses = static fn (array $keys): array => array_map(
            static fn (string $key): array => [
                'key' => $key,
                'label' => Report::STATUSES[$key] ?? ucfirst($key),
                'params' => ['status' => $key],
                'count' => (int) ($counts[$key] ?? 0),
                'dot' => self::statusDot($key),
            ],
            $keys,
        );

        $shortcuts = [];
        $needsSummary = (! $assignedScope && $canAssign)
            || $canChangeStatus
            || ($canBeAssigned && ! $assignedScope);
        $summary = $needsSummary ? self::summary($user) : ['unassigned' => 0, 'urgent' => 0, 'mine' => 0];

        if (! $assignedScope && $canAssign) {
            $shortcuts[] = [
                'key' => 'unassigned',
                'label' => 'Unassigned',
                'params' => ['assigned' => 'unassigned'],
                'count' => $summary['unassigned'],
                'icon' => 'fa-inbox',
            ];
        }

        if ($canAssign || $canChangeStatus) {
            $shortcuts[] = [
                'key' => 'urgent',
                'label' => 'Urgent',
                'params' => ['priority' => 'urgent'],
                'count' => $summary['urgent'],
                'icon' => 'fa-triangle-exclamation',
            ];
        }

        if ($canBeAssigned && ! $assignedScope) {
            $shortcuts[] = [
                'key' => 'mine',
                'label' => 'Assigned to me',
                'params' => ['assigned' => 'me'],
                'count' => $summary['mine'],
                'icon' => 'fa-user-check',
            ];
        }

        return [
            'primary' => [
                'key' => 'all',
                'label' => $assignedScope ? 'My cases' : 'All cases',
                'params' => [],
                'count' => array_sum($counts),
            ],
            'groups' => [
                [
                    'key' => 'active',
                    'label' => 'Active',
                    'items' => $buildStatuses(self::ACTIVE_STATUSES),
                ],
                [
                    'key' => 'closed',
                    'label' => 'Closed & resolved',
                    'items' => $buildStatuses(self::CLOSED_STATUSES),
                ],
            ],
            'shortcuts' => $shortcuts,
        ];
    }

    /**
     * Status totals for the cases the user is allowed to see.
     *
     * @return array<string, int>
     */
    public static function statusCounts(User $user): array
    {
        return self::remember('status.'.$user->getKey(), static fn (): array => Report::query()
            ->visibleTo($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(static fn ($total): int => (int) $total)
            ->all());
    }

    /**
     * @return array{unassigned: int, urgent: int, mine: int}
     */
    private static function summary(User $user): array
    {
        return self::remember('summary.'.$user->getKey(), static function () use ($user): array {
            $row = Report::query()
                ->visibleTo($user)
                ->selectRaw('coalesce(sum(case when assigned_to is null then 1 else 0 end), 0) as unassigned')
                ->selectRaw('coalesce(sum(case when priority = ? then 1 else 0 end), 0) as urgent', ['urgent'])
                ->selectRaw('coalesce(sum(case when assigned_to = ? then 1 else 0 end), 0) as mine', [$user->getKey()])
                ->first();

            return [
                'unassigned' => (int) ($row?->unassigned ?? 0),
                'urgent' => (int) ($row?->urgent ?? 0),
                'mine' => (int) ($row?->mine ?? 0),
            ];
        });
    }

    public static function statusDot(string $status): string
    {
        return match ($status) {
            'triage' => 'bg-amber-500',
            'investigation' => 'bg-blue-500',
            'referred' => 'bg-purple-500',
            'resolved' => 'bg-green-600',
            'closed' => 'bg-slate-700',
            'duplicate', 'withdrawn' => 'bg-slate-400',
            default => 'bg-slate-400',
        };
    }

    /**
     * Cache the result for the current request so the sidebar, page controls,
     * and any other caller share a single query.
     */
    private static function remember(string $key, Closure $callback): mixed
    {
        $request = request();
        $attribute = 'case_queues.'.$key;

        if (! $request->attributes->has($attribute)) {
            $request->attributes->set($attribute, $callback());
        }

        return $request->attributes->get($attribute);
    }
}

<?php

namespace App\Support;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Append-only trail for security-sensitive access-control changes. IP
 * addresses are keyed hashes, matching the case audit log.
 */
class AccessAudit
{
    public static function log(
        ?User $actor,
        string $action,
        ?Model $target = null,
        ?string $summary = null,
        array $changes = [],
        ?Request $request = null,
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'summary' => $summary,
            'changes' => $changes ?: null,
            'ip_hash' => $request ? self::hash($request->ip()) : null,
            'created_at' => now(),
        ]);
    }

    private static function hash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}

<?php

namespace App\Support;

use App\Models\Report;
use App\Models\ReportAccessLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Append-only access trail for case data. IP addresses and user agents are
 * hashed with the application key so the log can detect repeats without
 * storing raw request metadata.
 */
class CaseAudit
{
    public static function log(
        Report $report,
        ?User $user,
        string $action,
        array $fields = [],
        ?string $reason = null,
        ?Request $request = null,
    ): ReportAccessLog {
        return ReportAccessLog::create([
            'report_id' => $report->id,
            'user_id' => $user?->id,
            'action' => $action,
            'fields_accessed' => $fields ?: null,
            'reason' => $reason,
            'ip_hash' => $request ? self::hash($request->ip()) : null,
            'user_agent_hash' => $request ? self::hash((string) $request->userAgent()) : null,
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

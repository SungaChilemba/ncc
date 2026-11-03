<?php

namespace App\Support;

use App\Models\Report;
use Illuminate\Http\Request;

/**
 * Holds a short-lived, PIN-verified grant for the reporter who is tracking a
 * case. The grant lives in the session so evidence uploads and downloads do
 * not have to resend the PIN on every request.
 */
class CaseAccess
{
    private const KEY = 'reporting.grant';

    public static function grant(Request $request, Report $report): void
    {
        $request->session()->put(self::KEY, [
            'report_id' => $report->id,
            'expires_at' => now()->addMinutes((int) config('reporting.grant_minutes', 15))->getTimestamp(),
        ]);
    }

    public static function currentReport(Request $request): ?Report
    {
        $grant = $request->session()->get(self::KEY);

        if (! is_array($grant) || empty($grant['report_id'])) {
            return null;
        }

        if (($grant['expires_at'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(self::KEY);

            return null;
        }

        return Report::find($grant['report_id']);
    }

    public static function hasGrantFor(Request $request, Report $report): bool
    {
        return self::currentReport($request)?->is($report) ?? false;
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }
}

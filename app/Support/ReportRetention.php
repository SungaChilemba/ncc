<?php

namespace App\Support;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Anonymises cases at the end of their retention period, or in response to a
 * valid erasure request.
 *
 * The case row is kept so national statistics stay accurate and the access
 * trail remains reviewable, but every direct identifier - names, contact
 * details, village, narrative, notes and evidence files - is removed. The
 * reporter PIN is rotated so the old reference can no longer be used.
 */
class ReportRetention
{
    public const REDACTED_NARRATIVE = '[Anonymised under the retention policy]';

    public function anonymise(Report $report, string $reason, ?User $actor = null, ?Request $request = null): bool
    {
        if ($report->anonymised_at !== null) {
            return false;
        }

        $this->purgeEvidence($report);
        $report->notes()->delete();

        $report->forceFill([
            'child_name' => null,
            'description' => self::REDACTED_NARRATIVE,
            'reporter_name' => null,
            'reporter_contact' => null,
            'reporter_contact_hash' => null,
            'village' => null,
            'is_anonymous' => true,
            'assigned_to' => null,
            'access_pin_hash' => Hash::make(Str::random(48)),
            'pin_attempts' => 0,
            'pin_locked_until' => null,
            'anonymised_at' => now(),
        ])->save();

        CaseAudit::log(
            $report,
            $actor,
            'anonymised',
            ['identity', 'notes', 'evidence'],
            $reason,
            $request,
        );

        return true;
    }

    private function purgeEvidence(Report $report): void
    {
        $evidence = $report->evidence()->withTrashed()->get();

        foreach ($evidence as $file) {
            $disk = Storage::disk($file->disk);

            if ($file->path && $disk->exists($file->path)) {
                $disk->delete($file->path);
            }

            $file->forceDelete();
        }
    }
}

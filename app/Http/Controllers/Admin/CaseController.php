<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportEvent;
use App\Models\ReportEvidence;
use App\Models\ReportNote;
use App\Models\User;
use App\Support\CaseAudit;
use App\Support\CaseQueues;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Report::class);

        $user = $request->user();

        $query = Report::query()
            ->visibleTo($user)
            ->with('assignee');

        if (($status = $request->query('status')) && array_key_exists($status, Report::STATUSES)) {
            $query->where('status', $status);
        }

        if (($priority = $request->query('priority')) && in_array($priority, Report::PRIORITIES, true)) {
            $query->where('priority', $priority);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));

            $query->where(function ($inner) use ($term) {
                $inner->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('district', 'like', "%{$term}%")
                    ->orWhere('village', 'like', "%{$term}%");
            });
        }

        match ($request->query('assigned')) {
            'me' => $query->where('assigned_to', $user->id),
            'unassigned' => $query->whereNull('assigned_to'),
            default => null,
        };

        $reports = $query->orderByDesc('received_at')
            ->paginate(20)
            ->withQueryString();

        $counts = collect(CaseQueues::statusCounts($user));

        return view('admin.cases.index', [
            'reports' => $reports,
            'counts' => $counts,
            'user' => $user,
            'recoveryCodes' => $request->session()->get('case_recovery_codes', []),
        ]);
    }

    public function show(Request $request, Report $report)
    {
        Gate::authorize('view', $report);

        $user = $request->user();
        $canViewIdentity = Gate::allows('viewIdentity', $report);
        $canViewEvidence = Gate::allows('downloadEvidence', $report);
        $canViewAudit = Gate::allows('viewAudit', $report);

        $report->load(['assignee', 'events.creator']);

        if ($canViewIdentity) {
            $report->load(['notes.user', 'evidence.uploader']);
        } else {
            $report->load('evidence');
        }

        $logs = $canViewAudit
            ? $report->accessLogs()->with('user')->limit(100)->get()
            : collect();

        $assignableUsers = Gate::allows('assign', $report)
            ? User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('can_be_assigned', true))
                ->orderBy('name')
                ->get()
            : collect();

        $availableStatuses = collect(Report::TRANSITIONS[$report->status] ?? [])
            ->filter(fn (string $status) => Gate::allows('transition', [$report, $status]))
            ->mapWithKeys(fn (string $status) => [$status => Report::STATUSES[$status] ?? $status]);

        CaseAudit::log(
            $report,
            $user,
            'viewed',
            $canViewIdentity ? ['case', 'identity'] : ['case'],
            null,
            $request,
        );

        if ($canViewIdentity) {
            CaseAudit::log($report, $user, 'identity_viewed', ['identity'], null, $request);
        }

        return view('admin.cases.show', [
            'report' => $report,
            'canViewIdentity' => $canViewIdentity,
            'canViewEvidence' => $canViewEvidence,
            'canViewAudit' => $canViewAudit,
            'logs' => $logs,
            'assignableUsers' => $assignableUsers,
            'availableStatuses' => $availableStatuses,
        ]);
    }

    public function assign(Request $request, Report $report)
    {
        Gate::authorize('assign', $report);

        $assignableIds = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query
                ->where('is_active', true)
                ->where('can_be_assigned', true))
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::in($assignableIds),
            ],
        ]);

        $assignee = filled($validated['assigned_to'] ?? null)
            ? User::find($validated['assigned_to'])
            : null;

        $report->forceFill(['assigned_to' => $assignee?->id])->save();

        ReportEvent::create([
            'report_id' => $report->id,
            'type' => 'assigned',
            'message' => $assignee
                ? "Case assigned to {$assignee->name}"
                : 'Case returned to the unassigned queue',
            'is_public' => false,
            'created_by' => $request->user()->id,
        ]);

        CaseAudit::log(
            $report,
            $request->user(),
            'assigned',
            ['assigned_to'],
            $assignee?->name ?? 'unassigned',
            $request,
        );

        return back()->with(
            'status',
            $assignee ? "Case assigned to {$assignee->name}." : 'Case unassigned.',
        );
    }

    public function updateStatus(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Report::STATUSES))],
            'outcome' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $target = $validated['status'];

        Gate::authorize('transition', [$report, $target]);

        $from = $report->status;
        $attributes = ['status' => $target];

        if (in_array($target, Report::CLOSED_STATUSES, true)) {
            $attributes['closed_at'] = now();
            $attributes['closure_outcome'] = $validated['outcome'] ?? null;
            $attributes['retention_delete_at'] = now()->addYears((int) config('reporting.retention_years', 2));
        } elseif ($report->isClosed()) {
            $attributes['closed_at'] = null;
            $attributes['closure_outcome'] = null;
            $attributes['retention_delete_at'] = null;
        }

        $report->forceFill($attributes)->save();

        ReportEvent::create([
            'report_id' => $report->id,
            'from_status' => $from,
            'to_status' => $target,
            'type' => 'status_changed',
            'message' => $report->publicStatusMessage(),
            'is_public' => true,
            'created_by' => $request->user()->id,
        ]);

        if (filled($validated['note'] ?? null)) {
            ReportNote::create([
                'report_id' => $report->id,
                'user_id' => $request->user()->id,
                'body' => $validated['note'],
            ]);
        }

        CaseAudit::log(
            $report,
            $request->user(),
            'status_changed',
            ['status'],
            "{$from} -> {$target}",
            $request,
        );

        return back()->with('status', 'Case status updated to '.$report->statusLabel().'.');
    }

    public function storeNote(Request $request, Report $report)
    {
        Gate::authorize('addNote', $report);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ReportNote::create([
            'report_id' => $report->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        CaseAudit::log($report, $request->user(), 'note_added', ['notes'], null, $request);

        return back()->with('status', 'Case note added.');
    }

    public function storeEvidence(Request $request, Report $report)
    {
        Gate::authorize('uploadEvidence', $report);

        $maxFiles = (int) config('reporting.max_files_per_request', 5);

        $request->validate([
            'evidence' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'evidence.*' => [
                'required',
                File::types(config('reporting.allowed_extensions'))
                    ->max((int) config('reporting.max_file_mb', 20).'mb'),
            ],
        ]);

        $files = $request->file('evidence', []);
        $existing = $report->evidence()->count();

        if ($existing + count($files) > (int) config('reporting.max_total_files', 20)) {
            return back()->withErrors([
                'evidence' => 'This case has reached the maximum of '
                    .config('reporting.max_total_files', 20).' evidence files.',
            ]);
        }

        $disk = config('reporting.evidence_disk', 'report_evidence');

        foreach ($files as $file) {
            $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
            $path = $file->storeAs(
                'reports/'.$report->id,
                Str::uuid()->toString().($extension ? '.'.$extension : ''),
                $disk,
            );

            $report->evidence()->create([
                'uploaded_by' => $request->user()->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
                'mime_type' => $file->getMimeType(),
                'size' => (int) $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()) ?: null,
                'scan_status' => 'not_scanned',
                'uploaded_at' => now(),
            ]);
        }

        ReportEvent::create([
            'report_id' => $report->id,
            'type' => 'evidence_added',
            'message' => count($files).' evidence file(s) added by case staff',
            'is_public' => false,
            'created_by' => $request->user()->id,
        ]);

        CaseAudit::log(
            $report,
            $request->user(),
            'evidence_uploaded',
            ['evidence'],
            count($files).' file(s)',
            $request,
        );

        return back()->with('status', count($files).' evidence file(s) uploaded.');
    }

    public function downloadEvidence(Request $request, Report $report, ReportEvidence $evidence): StreamedResponse
    {
        abort_unless((int) $evidence->report_id === (int) $report->id, 404);

        Gate::authorize('view', $report);
        Gate::authorize('downloadEvidence', $report);

        $disk = Storage::disk($evidence->disk);

        abort_unless($disk->exists($evidence->path), 404);

        CaseAudit::log(
            $report,
            $request->user(),
            'evidence_downloaded',
            ['evidence'],
            $evidence->original_name,
            $request,
        );

        return $disk->download($evidence->path, $evidence->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, private',
            'Content-Security-Policy' => 'sandbox',
        ]);
    }

    public function audit(Request $request, Report $report)
    {
        Gate::authorize('viewAudit', $report);

        $logs = $report->accessLogs()->with('user')->paginate(50);

        CaseAudit::log($report, $request->user(), 'audit_viewed', ['audit_log'], null, $request);

        return view('admin.cases.audit', [
            'report' => $report,
            'logs' => $logs,
        ]);
    }
}

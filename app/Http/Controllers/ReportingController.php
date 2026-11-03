<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportEvent;
use App\Models\ReportEvidence;
use App\Support\CaseAccess;
use App\Support\CaseAudit;
use App\Support\CmsContent;
use App\Support\ReportReference;
use App\Support\ReportStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingController extends Controller
{
    public function __construct(private readonly CmsContent $content) {}

    public function index(Request $request)
    {
        $tracked = CaseAccess::currentReport($request);
        $year = (int) $request->query('year', now()->year);

        return view('pages.reporting', array_merge([
            'cmsContent' => $this->content->forSection('reporting'),
            'cmsHeroes' => $this->content->type('reporting', 'hero'),
            'cmsCards' => $this->content->type('reporting', 'card'),
            'cmsCtas' => $this->content->type('reporting', 'cta'),
            'cmsNews' => $this->content->type('reporting', 'news'),
            'cmsResources' => $this->content->type('reporting', 'resource'),
            'cmsGallery' => $this->content->type('reporting', 'gallery'),
            'suppressCmsBlocks' => true,
        ], [
            'tab' => session('report_tab') ?? $request->query('tab', 'complaint'),
            'stats' => app(ReportStats::class)->forYear($year),
            'trackedReport' => $tracked,
            'trackedTimeline' => $tracked?->events()->where('is_public', true)->get() ?? collect(),
            'trackedEvidence' => $tracked?->evidence()->latest('uploaded_at')->get() ?? collect(),
            'estimatedResponse' => config('reporting.estimated_response'),
            'hotline' => config('reporting.hotline'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateForTab($request, [
            'child_age' => ['required', 'integer', 'min:0', 'max:17'],
            'child_name' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:120'],
            'village' => ['nullable', 'string', 'max:160'],
            'child_gender' => ['required', 'string', 'in:'.implode(',', Report::GENDERS)],
            'violation_type' => ['required', 'string', 'in:'.implode(',', Report::VIOLATION_TYPES)],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'reporter_name' => ['nullable', 'string', 'max:255'],
            'reporter_contact' => ['nullable', 'string', 'max:255'],
            'is_anonymous' => ['nullable', 'boolean'],
            'consent_given' => ['accepted'],
            'urgent' => ['nullable', 'boolean'],
        ], 'complaint');

        $reference = ReportReference::generate();
        $pin = ReportReference::pin();
        $contact = trim((string) ($validated['reporter_contact'] ?? ''));

        $report = DB::transaction(function () use ($validated, $reference, $pin, $contact, $request) {
            $report = Report::create([
                'reference_number' => $reference,
                'access_pin_hash' => Hash::make($pin),
                'status' => 'received',
                'priority' => $request->boolean('urgent') ? 'urgent' : 'standard',
                'violation_type' => $validated['violation_type'],
                'incident_date' => $validated['incident_date'] ?? null,
                'district' => $validated['district'],
                'village' => $validated['village'] ?? null,
                'child_age' => $validated['child_age'],
                'child_gender' => $validated['child_gender'],
                'child_name' => $validated['child_name'] ?? null,
                'description' => $validated['description'],
                'reporter_name' => $validated['reporter_name'] ?? null,
                'reporter_contact' => $contact !== '' ? $contact : null,
                'reporter_contact_hash' => $contact !== ''
                    ? hash_hmac('sha256', Str::lower($contact), (string) config('app.key'))
                    : null,
                // A reporter may choose to remain anonymous but still leave a
                // safe contact channel for follow-up; never discard the contact.
                'is_anonymous' => $request->boolean('is_anonymous')
                    || ($contact === '' && blank($validated['reporter_name'] ?? null)),
                'consent_given' => true,
                'received_at' => now(),
            ]);

            ReportEvent::create([
                'report_id' => $report->id,
                'to_status' => 'received',
                'type' => 'received',
                'message' => 'Complaint received and queued for assessment.',
                'is_public' => true,
            ]);

            CaseAudit::log($report, null, 'submitted_by_reporter', ['reference'], null, $request);

            return $report;
        });

        CaseAccess::grant($request, $report);

        return redirect()
            ->route('reporting', ['tab' => 'complaint'])
            ->with('report_status', 'Complaint received.')
            ->with('report_reference', $report->reference_number)
            ->with('report_pin', $pin);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $report = $this->verifiedReport($request, 'pin', 'track');

        CaseAccess::grant($request, $report);
        CaseAudit::log($report, null, 'tracked_by_reporter', ['status', 'timeline'], null, $request);

        return redirect()
            ->route('reporting', ['tab' => 'track'])
            ->with('tracking_status', 'Case verified. You can now attach evidence for the next '
                .config('reporting.grant_minutes', 15).' minutes.');
    }

    public function storeEvidence(Request $request): RedirectResponse
    {
        $granted = CaseAccess::currentReport($request);
        $maxFiles = (int) config('reporting.max_files_per_request', 5);

        $rules = [
            'reference_number' => ['required', 'string', 'max:32'],
            'evidence' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'evidence.*' => [
                'required',
                File::types(config('reporting.allowed_extensions'))
                    ->max((int) config('reporting.max_file_mb', 20).'mb'),
            ],
        ];

        // The PIN is only required when there is no live session grant for the
        // case. This lets a verified reporter attach several files without
        // re-entering the PIN while the 15-minute window is open.
        if (! $granted) {
            $rules['pin'] = ['required', 'digits:6'];
        }

        $validated = $this->validateForTab($request, $rules, 'evidence');

        $reference = ReportReference::normalize($validated['reference_number']);
        $report = $granted && $granted->reference_number === $reference
            ? $granted
            : $this->verifiedReport($request, 'pin', 'evidence');

        $files = $request->file('evidence', []);
        $totalBytes = collect($files)->sum(fn ($file) => (int) $file->getSize());
        $maxBytes = (int) config('reporting.max_total_mb', 100) * 1024 * 1024;

        if ($totalBytes > $maxBytes) {
            return back()
                ->withErrors(['evidence' => 'The selected files exceed the allowed total size of '
                    .config('reporting.max_total_mb', 100).' MB.'])
                ->withInput();
        }

        $existing = $report->evidence()->count();

        if ($existing + count($files) > (int) config('reporting.max_total_files', 20)) {
            return back()
                ->withErrors(['evidence' => 'This case has reached the maximum of '
                    .config('reporting.max_total_files', 20).' evidence files.'])
                ->withInput();
        }

        $disk = (string) config('reporting.evidence_disk', 'report_evidence');

        DB::transaction(function () use ($report, $files, $disk, $request) {
            foreach ($files as $file) {
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $path = $file->storeAs(
                    'reports/'.$report->id,
                    (string) Str::uuid().'.'.$extension,
                    $disk,
                );

                ReportEvidence::create([
                    'report_id' => $report->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => Str::limit($file->getClientOriginalName(), 240, ''),
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
                'message' => count($files).' evidence file(s) uploaded by the reporter.',
                'is_public' => false,
            ]);

            CaseAudit::log($report, null, 'evidence_uploaded_by_reporter', ['evidence'], null, $request);
        });

        CaseAccess::grant($request, $report);

        return redirect()
            ->route('reporting', ['tab' => 'evidence'])
            ->with('evidence_status', count($files).' evidence file(s) attached to '.$report->reference_number.'.')
            ->with('evidence_reference', $report->reference_number);
    }

    public function downloadEvidence(Request $request, ReportEvidence $evidence): StreamedResponse
    {
        $report = CaseAccess::currentReport($request);

        abort_unless($report && (int) $evidence->report_id === (int) $report->id, 403);

        CaseAudit::log($report, null, 'evidence_downloaded_by_reporter', ['evidence'], null, $request);

        $disk = Storage::disk($evidence->disk);

        abort_unless($disk->exists($evidence->path), 404);

        return response()->streamDownload(
            fn () => fpassthru($disk->readStream($evidence->path)),
            $evidence->original_name,
            [
                'Content-Type' => $evidence->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, private',
                'Content-Security-Policy' => 'sandbox',
            ],
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        CaseAccess::forget($request);

        return redirect()
            ->route('reporting', ['tab' => 'track'])
            ->with('tracking_status', 'You have signed out of this case on this device.');
    }

    /**
     * Resolve a reference + PIN pair. Errors are deliberately generic so the
     * endpoint cannot be used to discover which references exist.
     */
    private function verifiedReport(Request $request, string $errorKey, string $tab): Report
    {
        $validated = $this->validateForTab($request, [
            'reference_number' => ['required', 'string', 'max:32'],
            'pin' => ['required', 'digits:6'],
        ], $tab);

        $reference = ReportReference::normalize($validated['reference_number']);
        $report = Report::withTrashed()->where('reference_number', $reference)->first();

        if (! $report || $report->trashed() || ! $report->verifyPin($validated['pin'])) {
            $request->session()->flash('report_tab', $tab);

            throw ValidationException::withMessages([
                $errorKey => 'The reference number or PIN is incorrect.',
            ]);
        }

        return $report;
    }

    /**
     * Validate input and remember which tab should stay open when validation
     * fails, so a bad upload does not bounce the reporter back to a different
     * panel.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function validateForTab(Request $request, array $rules, string $tab): array
    {
        try {
            return $request->validate($rules);
        } catch (ValidationException $exception) {
            $request->session()->flash('report_tab', $tab);

            throw $exception;
        }
    }
}

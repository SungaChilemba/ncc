@extends('admin.layouts.app')

@php
    $statusClasses = [
        'received' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300',
        'triage' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400',
        'investigation' => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400',
        'referred' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300',
        'resolved' => 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400',
        'closed' => 'bg-slate-800 dark:bg-slate-700 text-white',
        'duplicate' => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400',
        'withdrawn' => 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400',
    ];
    $currentUser = auth()->user();
    $canAssign = $currentUser->can('assign', $report);
    $canChangeStatus = $currentUser->can('changeStatus', $report);
    $canAddNote = $currentUser->can('addNote', $report);
    $canUploadEvidence = $currentUser->can('uploadEvidence', $report);
@endphp

@section('title', 'Case ' . $report->reference_number)
@section('heading', 'Case ' . $report->reference_number)
@section('subheading', 'Review the report, record decisions, and keep an auditable trail.')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.cases.index') }}" class="text-sm font-semibold text-green-700 dark:text-green-400 hover:underline">&larr; Back to case queue</a>
        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <span class="rounded-full px-2.5 py-1 {{ $statusClasses[$report->status] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $report->statusLabel() }}</span>
            <span class="rounded-full px-2.5 py-1 {{ $report->priority === 'urgent' ? 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">{{ ucfirst($report->priority) }} priority</span>
            <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-400">Received {{ $report->received_at?->format('j M Y') ?? '—' }}</span>
            @if ($report->isOverdue())
                <span class="rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-1 text-red-700 dark:text-red-400">Overdue for this stage</span>
            @endif
        </div>
    </div>

    @if (! $canViewIdentity)
        <div class="mb-5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 p-4 text-sm text-slate-700 dark:text-slate-300">
            <p class="font-semibold">Identity-redacted view</p>
            <p class="mt-1">Your auditor role can review case metadata, status history, evidence counts, and the access trail. Identifying details, narrative content, evidence files, and notes are hidden.</p>
        </div>
    @endif

    @if ($report->isAnonymised())
        <div class="mb-5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 text-sm text-slate-700 dark:text-slate-300">
            <p class="font-semibold">This case has been anonymised</p>
            <p class="mt-1">The retention period ended on {{ $report->anonymised_at?->format('j M Y') }}. Direct identifiers, notes, and evidence were removed. The de-identified record is kept only for national statistics and audit purposes.</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <div class="space-y-6">
            <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Case summary</h2>

                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Reference</dt>
                        <dd class="font-mono text-base font-semibold text-slate-900 dark:text-slate-100">{{ $report->reference_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Violation type</dt>
                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->violation_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Assigned officer</dt>
                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Last updated</dt>
                        <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->updated_at?->format('j M Y, H:i') }}</dd>
                    </div>
                    @if ($report->closed_at)
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Closed</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->closed_at->format('j M Y') }}</dd>
                        </div>
                    @endif
                    @if ($report->retention_delete_at)
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Scheduled deletion</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->retention_delete_at->format('j M Y') }}</dd>
                        </div>
                    @endif
                    @if ($report->closure_outcome)
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500 dark:text-slate-400">Closure outcome</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->closure_outcome }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($canViewIdentity)
                <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Report details</h2>

                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Child's name</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->child_name ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Age / gender</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->child_age }} · {{ $report->child_gender }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">District</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->district }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Village / T/A</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->village ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Incident date</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->incident_date?->format('j M Y') ?? 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Reporter</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">
                                {{ $report->reporter_name ?: ($report->is_anonymous ? 'Anonymous' : 'Not provided') }}
                                @if ($report->is_anonymous)
                                    <span class="ml-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-xs text-slate-600 dark:text-slate-400">Anonymous</span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-slate-500 dark:text-slate-400">Contact</dt>
                            <dd class="font-semibold text-slate-900 dark:text-slate-100">{{ $report->reporter_contact ?: 'No contact details supplied' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 rounded-md bg-slate-50 dark:bg-slate-800/50 p-4">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Narrative</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $report->description }}</p>
                    </div>
                </section>
            @endif

            <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Case timeline</h2>
                    @if ($canViewAudit)
                        <a href="{{ route('admin.cases.audit', $report) }}" class="text-sm font-semibold text-green-700 dark:text-green-400 hover:underline">Full access trail</a>
                    @endif
                </div>

                <ol class="mt-4 space-y-4">
                    @forelse ($report->events as $event)
                        <li class="border-l-2 border-green-600 dark:border-green-500 pl-4">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $event->message ?: \Illuminate\Support\Str::headline($event->type) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ $event->created_at?->format('j M Y, H:i') }}
                                @if ($event->creator)
                                    · {{ $event->creator->name }}
                                @endif
                                @unless ($event->is_public)
                                    · internal
                                @endunless
                            </p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500 dark:text-slate-400">No events recorded yet.</li>
                    @endforelse
                </ol>
            </section>

            @if ($report->relationLoaded('notes'))
                <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Internal notes</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Notes are encrypted at rest and never shown to the reporter.</p>

                    @if ($canAddNote)
                        <form method="POST" action="{{ route('admin.cases.notes.store', $report) }}" class="mt-4 space-y-3">
                            @csrf
                            <textarea name="body" rows="3" required placeholder="Record a decision, contact attempt, or handover note"
                                      class="w-full rounded-md border border-slate-300 dark:border-slate-700 p-3 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">{{ old('body') }}</textarea>
                            @error('body')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="rounded-md bg-slate-900 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:hover:bg-slate-600">Add note</button>
                        </form>
                    @endif

                    <ul class="mt-5 space-y-3">
                        @forelse ($report->notes as $note)
                            <li class="rounded-md bg-slate-50 dark:bg-slate-800/50 p-3 text-sm">
                                <p class="whitespace-pre-line text-slate-700 dark:text-slate-300">{{ $note->body }}</p>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $note->user?->name ?? 'System' }} · {{ $note->created_at?->format('j M Y, H:i') }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500 dark:text-slate-400">No notes have been added.</li>
                        @endforelse
                    </ul>
                </section>
            @endif

            <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Evidence</h2>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $report->evidence->count() }} file(s)</span>
                </div>

                @if ($canViewEvidence)
                    <ul class="mt-4 space-y-2 text-sm">
                        @forelse ($report->evidence->sortByDesc('uploaded_at') as $evidence)
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-slate-50 dark:bg-slate-800/50 p-3">
                                <div class="min-w-0">
                                    <p class="break-all font-semibold text-slate-800 dark:text-slate-200">{{ $evidence->original_name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $evidence->size_for_humans }} · uploaded {{ $evidence->uploaded_at?->format('j M Y, H:i') }}</p>
                                </div>
                                <a href="{{ route('admin.cases.evidence.download', [$report, $evidence]) }}" class="rounded-md border border-green-700 dark:border-green-500 px-3 py-1.5 text-xs font-semibold text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40">Download</a>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500 dark:text-slate-400">No evidence has been attached to this case.</li>
                        @endforelse
                    </ul>

                    @if ($canUploadEvidence)
                        <form method="POST" action="{{ route('admin.cases.evidence.store', $report) }}" enctype="multipart/form-data" class="mt-5 space-y-3 border-t border-slate-200 dark:border-slate-800 pt-5">
                            @csrf
                            <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200" for="evidence">Add evidence files</label>
                            <input id="evidence" name="evidence[]" type="file" multiple accept=".jpg,.jpeg,.png,.mp3,.mp4,.pdf"
                                   class="block w-full rounded-md border border-slate-300 dark:border-slate-700 p-2 text-sm">
                            <p class="text-xs text-slate-500 dark:text-slate-400">JPEG, PNG, MP3, MP4, or PDF. Max {{ config('reporting.max_file_mb', 20) }} MB each.</p>
                            @error('evidence')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @error('evidence.*')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="rounded-md border border-green-700 dark:border-green-500 px-4 py-2 text-sm font-semibold text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40">Upload evidence</button>
                        </form>
                    @endif
                @else
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Evidence metadata is restricted for your role.</p>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            @if ($canAssign)
                <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Assignment</h2>
                    <form method="POST" action="{{ route('admin.cases.assign', $report) }}" class="mt-4 space-y-3">
                        @csrf
                        <select name="assigned_to" class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                            <option value="">Unassigned</option>
                            @foreach ($assignableUsers as $assignable)
                                <option value="{{ $assignable->id }}" @selected((int) $report->assigned_to === $assignable->id)>
                                    {{ $assignable->name }} — {{ $assignable->caseRoleLabel() }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="w-full rounded-md bg-green-700 dark:bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">Save assignment</button>
                    </form>
                </section>
            @endif

            @if ($canChangeStatus)
                <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Update status</h2>
                    @if ($availableStatuses->isEmpty())
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No further transitions are available from this status for your role.</p>
                    @else
                        <form method="POST" action="{{ route('admin.cases.status', $report) }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <label for="status" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">New status</label>
                                <select id="status" name="status" required class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm">
                                    @foreach ($availableStatuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="outcome" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Closure outcome</label>
                                <input id="outcome" name="outcome" type="text" value="{{ old('outcome') }}" placeholder="Used when closing the case"
                                       class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="status-note" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Internal note</label>
                                <textarea id="status-note" name="note" rows="3" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 p-2 text-sm">{{ old('note') }}</textarea>
                            </div>
                            <button type="submit" class="w-full rounded-md bg-slate-900 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:hover:bg-slate-600">Update status</button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($canViewAudit)
                <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Recent access</h2>
                        <a href="{{ route('admin.cases.audit', $report) }}" class="text-xs font-semibold text-green-700 dark:text-green-400 hover:underline">View all</a>
                    </div>
                    <ul class="mt-3 space-y-2 text-xs text-slate-600 dark:text-slate-400">
                        @forelse ($logs->take(5) as $log)
                            <li class="rounded-md bg-slate-50 dark:bg-slate-800/50 p-2">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::headline($log->action) }}</span>
                                · {{ $log->user?->name ?? 'Reporter' }}
                                <br>{{ $log->created_at?->format('j M Y, H:i') }}
                            </li>
                        @empty
                            <li>No access has been logged yet.</li>
                        @endforelse
                    </ul>
                </section>
            @endif
        </aside>
    </div>
@endsection

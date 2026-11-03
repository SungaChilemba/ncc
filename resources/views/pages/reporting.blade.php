@extends("layouts.app")

@section("title", "Reporting")

@section("content")
    @php
        $hero = ($cmsHeroes ?? collect())->first();
        $workflowCards = ($cmsCards ?? collect())->where("category", "Reporting Workflow")->values();
        $activeTab = in_array($tab ?? "", ["complaint", "evidence", "track", "stats"], true)
            ? $tab
            : "complaint";
        $statLabels = array_keys($stats["by_violation"] ?? []);
        $statValues = array_values($stats["by_violation"] ?? []);
        $hasStatsChart = collect($statValues)->contains(fn ($value) => $value !== null);
        $number = fn ($value) => $value === null ? "—" : number_format((int) $value);
    @endphp

    <section class="bg-white px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_28rem] lg:items-start">
                <div class="space-y-8">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-green-700">{{ $hero?->subtitle ?: "File complaints, attach evidence, and track case progress." }}</p>
                        <h1 class="mt-3 text-3xl font-bold text-slate-950 md:text-5xl">{{ $hero?->title ?: "Reporting and Tracking" }}</h1>
                        @if ($hero?->description)
                            <p class="mt-4 max-w-3xl leading-7 text-gray-700">{{ $hero->description }}</p>
                        @endif
                    </div>

                    @if (session("report_reference"))
                        <div class="rounded-lg border border-green-200 bg-green-50 p-5 text-sm text-green-900">
                            <p class="font-semibold">{{ session("report_status", "Complaint received.") }}</p>
                            <p class="mt-3">Your case reference is:</p>
                            <p class="mt-1 select-all font-mono text-2xl font-bold tracking-wider">{{ session("report_reference") }}</p>
                            @if (session("report_pin"))
                                <p class="mt-3">Your access PIN is:</p>
                                <p class="mt-1 select-all font-mono text-2xl font-bold tracking-[0.4em]">{{ session("report_pin") }}</p>
                                <p class="mt-3 font-semibold text-red-700">Write the reference and PIN down now. The PIN is shown only once.</p>
                            @endif
                            <p class="mt-3">You can use them to attach evidence and track this case. Keep them private; anyone with both can view the case.</p>
                        </div>
                    @endif

                    @if (session("evidence_status"))
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                            {{ session("evidence_status") }}
                        </div>
                    @endif

                    @if (session("tracking_status"))
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                            {{ session("tracking_status") }}
                        </div>
                    @endif

                    <div
                        class="rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-6"
                        x-data="{
                            tab: @js($activeTab),
                            selectedFiles: 0
                        }"
                    >
                        <div class="mb-8 flex flex-wrap gap-3">
                            @foreach (["complaint" => "File Complaint", "evidence" => "Attach Evidence", "track" => "Track Case", "stats" => "Statistics"] as $key => $label)
                                <button
                                    type="button"
                                    @click="tab = '{{ $key }}'"
                                    :class="tab === '{{ $key }}' ? 'bg-green-700 text-white' : 'border border-slate-300 bg-white text-gray-700 hover:border-green-700 hover:text-green-700'"
                                    class="rounded-md px-4 py-2 font-semibold transition"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        {{-- ------------------------------------------------------------
                             Complaint form
                        ------------------------------------------------------------- --}}
                        <div x-show="tab === 'complaint'" x-cloak x-transition.opacity.duration.200ms>
                            <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                                <h2 class="text-xl font-bold text-gray-800">File a Complaint</h2>
                                <p class="mt-2 text-sm text-gray-600">Fields marked * are required. Personal details are encrypted at rest and only visible to authorised case staff.</p>

                                <form class="mt-6 space-y-5" method="POST" action="{{ route("reporting.store") }}">
                                    @csrf
                                    @if ($errors->any() && ! $errors->has("pin"))
                                        <div class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                                            Please check the highlighted fields and try again.
                                        </div>
                                    @endif

                                    <div class="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <label for="child_age" class="block text-sm font-semibold text-slate-800">Child's Age *</label>
                                            <input id="child_age" name="child_age" type="number" min="0" max="17" value="{{ old("child_age") }}" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("child_age")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="child_name" class="block text-sm font-semibold text-slate-800">Child's Name</label>
                                            <input id="child_name" name="child_name" type="text" value="{{ old("child_name") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("child_name")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="district" class="block text-sm font-semibold text-slate-800">District *</label>
                                            <input id="district" name="district" type="text" value="{{ old("district") }}" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("district")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="village" class="block text-sm font-semibold text-slate-800">Village / T/A</label>
                                            <input id="village" name="village" type="text" value="{{ old("village") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("village")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="child_gender" class="block text-sm font-semibold text-slate-800">Child's Gender *</label>
                                            <select id="child_gender" name="child_gender" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                                @foreach (\App\Models\Report::GENDERS as $gender)
                                                    <option value="{{ $gender }}" @selected(old("child_gender") === $gender)>{{ $gender }}</option>
                                                @endforeach
                                            </select>
                                            @error("child_gender")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="violation_type" class="block text-sm font-semibold text-slate-800">Nature of Violation *</label>
                                            <select id="violation_type" name="violation_type" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                                @foreach (\App\Models\Report::VIOLATION_TYPES as $violationType)
                                                    <option value="{{ $violationType }}" @selected(old("violation_type") === $violationType)>{{ $violationType }}</option>
                                                @endforeach
                                            </select>
                                            @error("violation_type")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="incident_date" class="block text-sm font-semibold text-slate-800">Date of Incident</label>
                                            <input id="incident_date" name="incident_date" type="date" max="{{ now()->toDateString() }}" value="{{ old("incident_date") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("incident_date")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex items-end">
                                            <label class="flex items-start gap-2 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                                                <input type="checkbox" name="urgent" value="1" @checked(old("urgent")) class="mt-0.5">
                                                <span><span class="font-semibold">Urgent risk</span><br>I believe a child is in immediate danger.</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="description" class="block text-sm font-semibold text-slate-800">Description *</label>
                                        <textarea id="description" name="description" rows="5" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old("description") }}</textarea>
                                        @error("description")
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="border-t border-slate-200 pt-5">
                                        <h3 class="text-lg font-bold text-gray-700">Reporter Info (Optional)</h3>
                                        <p class="mt-1 text-sm text-gray-600">You may remain anonymous and still leave a safe contact channel for follow-up. Contact details are encrypted.</p>
                                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label for="reporter_name" class="block text-sm font-semibold text-slate-800">Your Name</label>
                                                <input id="reporter_name" name="reporter_name" type="text" value="{{ old("reporter_name") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                                @error("reporter_name")
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="reporter_contact" class="block text-sm font-semibold text-slate-800">Phone or Email</label>
                                                <input id="reporter_contact" name="reporter_contact" type="text" value="{{ old("reporter_contact") }}" placeholder="e.g. +265 999 000 000" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                                @error("reporter_contact")
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <label class="mt-4 flex items-start gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="is_anonymous" value="1" @checked(old("is_anonymous")) class="mt-1">
                                            <span>Please keep my identity anonymous. I understand any contact details I provide will still be kept private.</span>
                                        </label>
                                    </div>

                                    <label class="flex items-start gap-2 rounded-md bg-slate-50 p-3 text-sm text-gray-700">
                                        <input type="checkbox" name="consent_given" value="1" required @checked(old("consent_given")) class="mt-1">
                                        <span>I consent to this information being used to assess and respond to the complaint. *</span>
                                    </label>
                                    @error("consent_given")
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                        <button type="submit" class="rounded-md bg-red-600 px-6 py-2 font-semibold text-white transition hover:bg-red-700">Submit Complaint</button>
                                        <p class="text-sm text-gray-500">You will receive a reference number and PIN to attach evidence and track your case.</p>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- ------------------------------------------------------------
                             Evidence upload
                        ------------------------------------------------------------- --}}
                        <div x-show="tab === 'evidence'" x-cloak x-transition.opacity.duration.200ms>
                            <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                                <h2 class="text-xl font-bold text-gray-800">Attach Evidence</h2>

                                @if ($trackedReport)
                                    <div class="mt-4 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                                        Verified session for <strong>{{ $trackedReport->reference_number }}</strong>. You can attach files without re-entering your PIN for the next {{ config("reporting.grant_minutes", 15) }} minutes.
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-gray-600">Enter the case reference and PIN you received when you filed the complaint.</p>
                                @endif

                                <form class="mt-6 space-y-5" method="POST" action="{{ route("reporting.evidence.store") }}" enctype="multipart/form-data">
                                    @csrf
                                    @if ($trackedReport)
                                        <input type="hidden" name="reference_number" value="{{ $trackedReport->reference_number }}">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">Case Reference</p>
                                            <p class="mt-1 font-mono text-lg">{{ $trackedReport->reference_number }}</p>
                                        </div>
                                    @else
                                        <div>
                                            <label for="evidence_reference" class="block text-sm font-semibold text-slate-800">Case Reference Number</label>
                                            <input id="evidence_reference" name="reference_number" type="text" value="{{ old("reference_number", session("report_reference", session("evidence_reference"))) }}" placeholder="e.g. NCC-2026-A1B2C3" required class="mt-1 w-full rounded-md border border-slate-300 p-2 uppercase focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("reference_number")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="evidence_pin" class="block text-sm font-semibold text-slate-800">Access PIN</label>
                                            <input id="evidence_pin" name="pin" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required class="mt-1 w-full rounded-md border border-slate-300 p-2 font-mono tracking-[0.4em] focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("pin")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    <label class="block cursor-pointer rounded-md border-2 border-dashed border-slate-300 p-6 text-center transition hover:border-green-700">
                                        <span class="block text-gray-600">Drag and drop files here or click to upload</span>
                                        <span class="mt-3 inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white">Choose Files</span>
                                        <span class="mt-2 block text-sm text-gray-500">Allowed: JPEG, PNG, MP3, MP4, PDF (max {{ config("reporting.max_file_mb", 20) }} MB each, {{ config("reporting.max_files_per_request", 5) }} files per upload)</span>
                                        <span class="mt-2 block text-sm font-semibold text-green-700" x-show="selectedFiles" x-text="selectedFiles + ' file(s) selected'"></span>
                                        <input type="file" name="evidence[]" multiple accept=".jpg,.jpeg,.png,.mp3,.mp4,.pdf" required class="sr-only" @change="selectedFiles = $event.target.files.length">
                                    </label>
                                    @error("evidence")
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error("evidence.*")
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    <div class="border-l-4 border-yellow-400 bg-yellow-50 p-4 text-sm text-gray-700">
                                        <p class="font-semibold">Safe Evidence Guidance</p>
                                        <ul class="ml-5 mt-2 list-disc space-y-1">
                                            <li>Do not put yourself or a child at risk to capture evidence.</li>
                                            <li>Avoid identifying details if they could expose the reporter or child.</li>
                                            <li>Files are stored privately and are never given a public URL.</li>
                                        </ul>
                                    </div>

                                    <button type="submit" class="rounded-md bg-red-600 px-6 py-2 font-semibold text-white transition hover:bg-red-700">Submit Evidence</button>
                                </form>

                                @if ($trackedReport && $trackedEvidence->isNotEmpty())
                                    <div class="mt-8 border-t border-slate-200 pt-5">
                                        <h3 class="font-bold text-gray-800">Evidence already attached</h3>
                                        <ul class="mt-3 space-y-2 text-sm">
                                            @foreach ($trackedEvidence as $evidence)
                                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-slate-50 p-3">
                                                    <span class="break-all text-gray-700">{{ $evidence->original_name }}</span>
                                                    <span class="text-gray-500">{{ $evidence->size_for_humans }}</span>
                                                    <a href="{{ route("reporting.evidence.download", $evidence) }}" class="font-semibold text-green-700 hover:underline">Download</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ------------------------------------------------------------
                             Track a case
                        ------------------------------------------------------------- --}}
                        <div x-show="tab === 'track'" x-cloak x-transition.opacity.duration.200ms>
                            <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                                <h2 class="text-xl font-bold text-gray-800">Track Your Case</h2>

                                @if ($trackedReport)
                                    <div class="mt-6 rounded-md bg-slate-50 p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm text-gray-500">Case reference</p>
                                                <p class="font-mono text-xl font-bold text-gray-900">{{ $trackedReport->reference_number }}</p>
                                            </div>
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">{{ $trackedReport->publicStatusLabel() }}</span>
                                        </div>

                                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                            <div>
                                                <dt class="text-gray-500">Violation type</dt>
                                                <dd class="font-semibold text-gray-800">{{ $trackedReport->violation_type }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500">District</dt>
                                                <dd class="font-semibold text-gray-800">{{ $trackedReport->district }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500">Received</dt>
                                                <dd class="font-semibold text-gray-800">{{ $trackedReport->received_at?->format("j M Y") }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500">Priority</dt>
                                                <dd class="font-semibold text-gray-800">{{ ucfirst($trackedReport->priority) }}</dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="mt-6">
                                        <h3 class="font-bold text-gray-700">Case Timeline</h3>
                                        @if ($trackedTimeline->isEmpty())
                                            <p class="mt-2 text-sm text-gray-500">No public updates yet.</p>
                                        @else
                                            <ul class="mt-3 space-y-3 text-gray-600">
                                                @foreach ($trackedTimeline as $event)
                                                    <li class="border-l-2 border-green-600 pl-3">
                                                        <p class="font-semibold text-gray-800">{{ $event->message ?: ucfirst(str_replace("_", " ", $event->type)) }}</p>
                                                        <p class="text-sm text-gray-500">{{ $event->created_at?->format("j M Y, H:i") }}</p>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    <div class="mt-6">
                                        <h3 class="font-bold text-gray-700">Evidence</h3>
                                        @if ($trackedEvidence->isEmpty())
                                            <p class="mt-2 text-sm text-gray-500">No evidence has been attached to this case.</p>
                                        @else
                                            <ul class="mt-3 space-y-2 text-sm">
                                                @foreach ($trackedEvidence as $evidence)
                                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-slate-50 p-3">
                                                        <span class="break-all text-gray-700">{{ $evidence->original_name }}</span>
                                                        <span class="text-gray-500">{{ $evidence->size_for_humans }}</span>
                                                        <a href="{{ route("reporting.evidence.download", $evidence) }}" class="font-semibold text-green-700 hover:underline">Download</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    <p class="mt-4 text-sm text-gray-500">{{ $estimatedResponse }} For urgent updates, call <span class="font-bold">{{ $hotline }}</span>.</p>

                                    <form class="mt-5" method="POST" action="{{ route("reporting.track.logout") }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-red-600 hover:text-red-700">Sign out of this case on this device</button>
                                    </form>
                                @else
                                    <p class="mt-2 text-sm text-gray-600">Enter your reference number and PIN to see public case updates and attach evidence.</p>

                                    <form class="mt-6 space-y-5" method="POST" action="{{ route("reporting.track") }}">
                                        @csrf
                                        <div>
                                            <label for="track_reference" class="block text-sm font-semibold text-slate-800">Case Reference Number</label>
                                            <input id="track_reference" name="reference_number" type="text" value="{{ old("reference_number", session("report_reference")) }}" placeholder="e.g. NCC-2026-A1B2C3" required class="mt-1 w-full rounded-md border border-slate-300 p-2 uppercase focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("reference_number")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="track_pin" class="block text-sm font-semibold text-slate-800">Access PIN</label>
                                            <input id="track_pin" name="pin" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required class="mt-1 w-full rounded-md border border-slate-300 p-2 font-mono tracking-[0.4em] focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @error("pin")
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <button type="submit" class="rounded-md bg-green-700 px-6 py-2 font-semibold text-white transition hover:bg-green-800">View Case</button>
                                    </form>

                                    <p class="mt-4 text-sm text-gray-500">Lost your PIN? Call <span class="font-bold">{{ $hotline }}</span> and quote your reference number.</p>
                                @endif
                            </div>
                        </div>

                        {{-- ------------------------------------------------------------
                             Public statistics
                        ------------------------------------------------------------- --}}
                        <div x-show="tab === 'stats'" x-cloak x-transition.opacity.duration.200ms>
                            <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-800">Case Statistics {{ $stats["year"] }} — national level</h2>
                                        <p class="mt-1 text-sm text-gray-600">Fully anonymised. Counts below {{ $stats["min_cell"] }} are hidden to protect individuals.</p>
                                    </div>
                                    <form method="GET" action="{{ route("reporting") }}" class="flex items-end gap-2">
                                        <input type="hidden" name="tab" value="stats">
                                        <div>
                                            <label for="stats_year" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Year</label>
                                            <select id="stats_year" name="year" onchange="this.form.submit()" class="mt-1 rounded-md border border-slate-300 p-2 text-sm">
                                                @foreach (range(now()->year, max(now()->year - 4, 2020)) as $optionYear)
                                                    <option value="{{ $optionYear }}" @selected($stats["year"] === $optionYear)>{{ $optionYear }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </form>
                                </div>

                                <div class="mt-6 grid grid-cols-1 gap-4 text-center sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                        <h3 class="text-2xl font-bold text-green-700">{{ $number($stats["total"]) }}</h3>
                                        <p class="text-gray-600">Cases filed</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                        <h3 class="text-2xl font-bold text-green-700">{{ $number($stats["closed"]) }}</h3>
                                        <p class="text-gray-600">Cases closed</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                        <h3 class="text-2xl font-bold text-green-700">{{ $stats["resolution_rate"] === null ? "—" : $stats["resolution_rate"]."%" }}</h3>
                                        <p class="text-gray-600">Resolution rate</p>
                                    </div>
                                    <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                        <h3 class="text-2xl font-bold text-green-700">{{ $stats["average_response_days"] === null ? "—" : $stats["average_response_days"]." days" }}</h3>
                                        <p class="text-gray-600">Avg. response</p>
                                    </div>
                                </div>

                                @if ($hasStatsChart)
                                    <div class="mt-6 h-64">
                                        <canvas id="caseChart"></canvas>
                                    </div>
                                @else
                                    <p class="mt-6 rounded-md bg-slate-50 p-4 text-sm text-gray-600">There is not enough data for this year to publish a breakdown.</p>
                                @endif

                                @if (! empty($stats["by_violation"]))
                                    <div class="mt-6 overflow-hidden rounded-md border border-slate-200">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead class="bg-slate-50 text-left text-gray-600">
                                                <tr>
                                                    <th class="px-4 py-2 font-semibold">Violation type</th>
                                                    <th class="px-4 py-2 text-right font-semibold">Cases</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($stats["by_violation"] as $label => $count)
                                                    <tr>
                                                        <td class="px-4 py-2 text-gray-700">{{ $label ?: "Not specified" }}</td>
                                                        <td class="px-4 py-2 text-right font-semibold text-gray-800">{{ $number($count) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                <p class="mt-4 text-sm text-gray-500">All data is aggregated nationally. No district, village, reference number, or individual information is published. A dash means the figure was suppressed because it was too small.</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($workflowCards->isNotEmpty())
                    <aside class="rounded-lg border border-slate-200 bg-slate-50 p-5 lg:sticky lg:top-24">
                        <h2 class="text-lg font-bold text-slate-950">Reporting Instructions</h2>
                        <div class="mt-4 space-y-4">
                            @foreach ($workflowCards as $card)
                                <div class="rounded-lg bg-white p-4 shadow-sm">
                                    <h3 class="font-semibold text-green-700">{{ $card->title }}</h3>
                                    @if ($card->description)
                                        <p class="mt-1 text-sm leading-6 text-gray-600">{{ $card->description }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </section>

    @php
        // Reporting instructions are rendered in the sidebar above; show any
        // other published CMS cards rather than dropping them.
        $remainingCards = ($cmsCards ?? collect())
            ->reject(fn ($card) => $workflowCards->contains("id", $card->id))
            ->values();
    @endphp

    @include("partials.cms-public-blocks", ["cmsCards" => $remainingCards])
@endsection

@if ($hasStatsChart)
    @push("scripts")
        {{-- Chart.js is only needed on this page; load it here instead of sitewide. --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const ctx = document.getElementById("caseChart");

                if (!ctx || !window.Chart) {
                    return;
                }

                new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: @json($statLabels),
                        datasets: [{
                            label: "Cases by violation type",
                            data: @json($statValues),
                            backgroundColor: ["#16a34a", "#22c55e", "#4ade80", "#86efac", "#bbf7d0", "#dcfce7"],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                },
                            },
                        },
                    },
                });
            });
        </script>
    @endpush
@endif

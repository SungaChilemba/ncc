@extends("layouts.app")

@section("title", "Reporting")

@section("content")
    @php
        $hero = $cmsHero ?? null;
        $workflowCards = ($cmsCards ?? collect())->where("category", "Reporting Workflow")->values();
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

            <div
                class="rounded-lg border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-6"
                x-data="{
                    tab: '{{ session("report_tab", "complaint") }}',
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

                <div x-show="tab === 'complaint'" x-cloak x-transition.opacity.duration.200ms>
                    <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-xl font-bold text-gray-800">File a Complaint</h2>
                        @if (session("report_reference"))
                            <div class="mt-5 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                                {{ session("report_status", "Complaint captured.") }} Your reference number is <strong>{{ session("report_reference") }}</strong>. Use it to attach evidence or track this case.
                            </div>
                        @endif
                        <form class="mt-6 space-y-5" method="POST" action="{{ route("reporting.store") }}">
                            @csrf
                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="child_age" class="block text-sm font-semibold text-slate-800">Child's Age</label>
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
                                    <label for="district" class="block text-sm font-semibold text-slate-800">District</label>
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
                                    <label for="gender" class="block text-sm font-semibold text-slate-800">Gender</label>
                                    <select id="gender" name="gender" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                        @foreach (["Male", "Female", "Other"] as $gender)
                                            <option @selected(old("gender") === $gender)>{{ $gender }}</option>
                                        @endforeach
                                    </select>
                                    @error("gender")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="violation_type" class="block text-sm font-semibold text-slate-800">Nature of Violation</label>
                                    <select id="violation_type" name="violation_type" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                        @foreach (["Abuse", "Neglect", "Child Marriage", "Exploitation / Labour", "Other"] as $violationType)
                                            <option @selected(old("violation_type") === $violationType)>{{ $violationType }}</option>
                                        @endforeach
                                    </select>
                                    @error("violation_type")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="incident_date" class="block text-sm font-semibold text-slate-800">Date of Incident</label>
                                    <input id="incident_date" name="incident_date" type="date" value="{{ old("incident_date") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    @error("incident_date")
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-semibold text-slate-800">Description</label>
                                <textarea id="description" name="description" rows="5" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old("description") }}</textarea>
                                @error("description")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="border-t border-slate-200 pt-5">
                                <h3 class="text-lg font-bold text-gray-700">Reporter Info (Optional)</h3>
                                <div class="mt-4 grid gap-5 md:grid-cols-2">
                                    <div>
                                        <label for="reporter_name" class="block text-sm font-semibold text-slate-800">Your Name</label>
                                        <input id="reporter_name" name="reporter_name" type="text" value="{{ old("reporter_name") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                        @error("reporter_name")
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="preferred_contact" class="block text-sm font-semibold text-slate-800">Preferred Contact</label>
                                        <input id="preferred_contact" name="preferred_contact" type="text" value="{{ old("preferred_contact") }}" class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                        @error("preferred_contact")
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <button type="submit" class="rounded-md bg-red-600 px-6 py-2 font-semibold text-white transition hover:bg-red-700">Submit Complaint</button>
                                <p class="text-sm text-gray-500">You will receive a reference number to attach evidence and track your case.</p>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="tab === 'evidence'" x-cloak x-transition.opacity.duration.200ms>
                    <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-xl font-bold text-gray-800">Attach Evidence</h2>
                        @if (session("evidence_status"))
                            <div class="mt-5 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                                {{ session("evidence_status") }}
                            </div>
                        @endif
                        <form class="mt-6 space-y-5" method="POST" action="{{ route("reporting.evidence.store") }}" enctype="multipart/form-data">
                            @csrf
                            <div>
                                <label for="evidence_reference" class="block text-sm font-semibold text-slate-800">Case Reference Number</label>
                                <input id="evidence_reference" name="reference_number" type="text" value="{{ old("reference_number", session("report_reference", session("evidence_reference"))) }}" placeholder="e.g. NCC-2026-00847" required class="mt-1 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                                @error("reference_number")
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <label class="block cursor-pointer rounded-md border-2 border-dashed border-slate-300 p-6 text-center transition hover:border-green-700">
                                <span class="block text-gray-600">Drag and drop files here or click to upload</span>
                                <span class="mt-3 inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white">Choose Files</span>
                                <span class="mt-2 block text-sm text-gray-500">Allowed: JPEG, PNG, MP3, MP4, PDF (max 20 MB)</span>
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
                                    <li>Avoid identifying details if it puts you or a child at risk.</li>
                                    <li>Include a consent statement if uploading media showing a child.</li>
                                    <li>If upload fails, describe the content in the complaint description field instead.</li>
                                </ul>
                            </div>
                            <button type="submit" class="rounded-md bg-red-600 px-6 py-2 font-semibold text-white transition hover:bg-red-700">Submit Evidence</button>
                        </form>
                    </div>
                </div>

                <div x-show="tab === 'track'" x-cloak x-transition.opacity.duration.200ms>
                    <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-xl font-bold text-gray-800">Track Your Case</h2>
                        <input type="text" placeholder="e.g. NCC-2026-00847" class="mt-6 w-full rounded-md border border-slate-300 p-2 focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                        <div class="mt-5 rounded-md bg-slate-50 p-4">
                            <h3 class="font-bold text-gray-800">Case NCC-2026-00847</h3>
                            <p class="mt-2">Status: <span class="font-semibold text-green-700">Investigation ongoing</span></p>
                            <p>Violation type: Child marriage</p>
                            <p>Region: Southern - Blantyre</p>
                        </div>
                        <div class="mt-5">
                            <h4 class="font-bold text-gray-700">Case Timeline</h4>
                            <ul class="mt-2 space-y-2 text-gray-600">
                                <li>Complaint received - 12 Jan 2026</li>
                                <li>Under assessment - 14 Jan 2026</li>
                                <li class="font-semibold text-green-700">Investigation ongoing - 20 Jan 2026 (current)</li>
                                <li>Referred / resolved - Pending</li>
                            </ul>
                        </div>
                        <p class="mt-4 text-sm text-gray-500">Estimated resolution: 4-6 weeks. For updates, call <span class="font-bold">116</span>.</p>
                    </div>
                </div>

                <div x-show="tab === 'stats'" x-cloak x-transition.opacity.duration.200ms>
                    <div class="rounded-lg bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-xl font-bold text-gray-800">Case Statistics Jan-Dec 2024 - Fully anonymised</h2>
                        <div class="mt-6 grid grid-cols-1 gap-6 text-center md:grid-cols-3">
                            <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                <h3 class="text-2xl font-bold text-green-700">1,284</h3>
                                <p class="text-gray-600">Cases filed</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                <h3 class="text-2xl font-bold text-green-700">71%</h3>
                                <p class="text-gray-600">Resolution rate</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-4 shadow-sm">
                                <h3 class="text-2xl font-bold text-green-700">23 days</h3>
                                <p class="text-gray-600">Avg. response</p>
                            </div>
                        </div>
                        <div class="mt-6 h-64">
                            <canvas id="caseChart"></canvas>
                        </div>
                        <p class="mt-4 text-sm text-gray-500">All data is fully anonymised. No individual or identifiable information is published.</p>
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
@endsection

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
                    labels: ["Abuse / neglect", "Child marriage", "Exploitation / labour", "Violence", "Other"],
                    datasets: [{
                        label: "Cases by Violation Type",
                        data: [34, 27, 19, 12, 8],
                        backgroundColor: ["#16a34a", "#22c55e", "#4ade80", "#86efac", "#bbf7d0"],
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
                },
            });
        });
    </script>
@endpush

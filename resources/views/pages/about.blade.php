@extends("layouts.app")

@section("title", "About Us")

@section("content")
    @php
        $cards = $cmsCards ?? collect();
        $hero = $cmsHero ?? null;
        $stats = $cmsContent->get("stat", collect()) ?? collect();
        $storyCards = $cards->where("category", "Mandate, Mission & Vision")->values();
        $storyTabs = [
            "mandate" => $storyCards->firstWhere("slug", "our-mandate"),
            "mission" => $storyCards->firstWhere("slug", "our-mission"),
            "vision" => $storyCards->firstWhere("slug", "our-vision"),
        ];
        $fallbackStory = $storyCards->first();
        $commissioners = $cards->where("category", "Commissioners")->values();
        $leadership = $cards->where("category", "Organogram")->values();
        $executive = $leadership->firstWhere("slug", "chief-executive-officer") ?? $leadership->first();
        $managers = $leadership->reject(fn ($item) => $executive && $item->is($executive))->values();
        $exOfficials = $cards->where("category", "Ex-Officials")->values();
        $backgrounds = collect($storyTabs)
            ->map(fn ($item) => $item?->image_url ? $item->image_url . "?v=" . $item->updated_at->timestamp : null)
            ->filter()
            ->all();
        $featureIcons = [
            "mandate" => "fa-shield-halved",
            "mission" => "fa-scale-balanced",
            "vision" => "fa-bullhorn",
        ];
        $featureLabels = [
            "mandate" => "Protection",
            "mission" => "Policy & Law",
            "vision" => "Advocacy",
        ];
        $featureDescriptions = [
            "mandate" => "Safeguarding children from abuse, neglect, trafficking, and exploitation.",
            "mission" => "Coordinating policy, law, and institutional accountability for children.",
            "vision" => "Amplifying children's voices and strengthening national child rights action.",
        ];
    @endphp

    <section
        class="relative overflow-hidden rounded-lg px-4 py-14 sm:px-6 lg:px-8"
        x-data="{
            tab: 'mandate',
            cycleInterval: null,
            startCycle() {
                this.stopCycle();
                this.cycleInterval = setInterval(() => {
                    this.tab = this.tab === 'mandate' ? 'mission' : this.tab === 'mission' ? 'vision' : 'mandate';
                }, 8000);
            },
            stopCycle() {
                if (this.cycleInterval) {
                    clearInterval(this.cycleInterval);
                    this.cycleInterval = null;
                }
            }
        }"
        x-init="startCycle()"
        @mouseenter="stopCycle()"
        @mouseleave="startCycle()"
    >
        <div class="absolute inset-0">
            @if ($hero?->image_url)
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $hero->image_url }}?v={{ $hero->updated_at->timestamp }}')"></div>
            @endif
            @foreach (["mandate", "mission", "vision"] as $key)
                @if (! empty($backgrounds[$key]))
                    <div
                        x-show="tab === '{{ $key }}'"
                        x-cloak
                        x-transition.opacity.duration.700ms
                        class="absolute inset-0 bg-cover bg-center"
                        style="background-image: url('{{ $backgrounds[$key] }}')"
                    ></div>
                @endif
            @endforeach
            <div class="absolute inset-0 bg-green-950/65"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="mb-8 max-w-4xl text-white">
                <p class="text-sm font-semibold uppercase tracking-wide text-green-100">{{ $hero?->subtitle ?: "Championing the Rights of Children in Malawi" }}</p>
                <h1 class="mt-3 text-3xl font-bold md:text-5xl">{{ $hero?->title ?: "About Us" }}</h1>
                @if ($hero?->description)
                    <p class="mt-4 max-w-3xl text-base leading-7 text-white/90">{{ $hero->description }}</p>
                @endif
            </div>

            <div class="rounded-lg border border-white/30 bg-white/95 p-5 shadow-lg backdrop-blur sm:p-8">
                <div class="mb-8 flex flex-wrap items-center justify-center gap-3 border-b border-slate-200 pb-5">
                    @foreach (["mandate" => "Our Mandate", "mission" => "Our Mission", "vision" => "Our Vision"] as $key => $label)
                        <button
                            type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-green-700 text-green-700' : 'border-transparent text-gray-600 hover:text-green-700'"
                            class="border-b-4 px-4 py-3 font-semibold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                @foreach ($storyTabs as $key => $item)
                    @php
                        $tabItem = $item ?: $fallbackStory;
                    @endphp
                    @if ($tabItem)
                        <div x-show="tab === '{{ $key }}'" x-cloak class="space-y-7">
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                @foreach (["mandate", "mission", "vision"] as $feature)
                                    <div class="rounded-lg bg-slate-50 p-5 text-center shadow-sm">
                                        <i class="fa-solid {{ $featureIcons[$feature] }} mb-4 text-3xl text-green-700"></i>
                                        <h3 class="text-lg font-bold">{{ $featureLabels[$feature] }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-gray-600">{{ $featureDescriptions[$feature] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="rounded-lg bg-slate-50 p-6 shadow-sm sm:p-8">
                                <p class="text-sm font-semibold uppercase tracking-wide text-green-700">{{ $tabItem->subtitle }}</p>
                                <h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $tabItem->title }}</h2>
                                @if ($tabItem->description)
                                    <div class="mt-4 space-y-3 text-gray-700">
                                        @foreach (preg_split("/\n\s*\n/", $tabItem->description) as $paragraph)
                                            <p>{{ $paragraph }}</p>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="mt-6 flex flex-wrap gap-3">
                                    @foreach (["Child Protection", "Rights Monitoring", "Community Engagement", "Education Access", "Policy Reform"] as $tag)
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-4 py-12 sm:px-6 lg:px-8" x-data="{ peopleTab: 'commissioners' }">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-wrap gap-4 border-b border-slate-200">
                @foreach (["commissioners" => "Commissioners", "exOfficials" => "Ex-Officials", "management" => "Management"] as $key => $label)
                    <button
                        type="button"
                        @click="peopleTab = '{{ $key }}'"
                        :class="peopleTab === '{{ $key }}' ? 'border-green-700 text-green-700' : 'border-transparent text-gray-600 hover:text-green-700'"
                        class="border-b-2 px-4 py-3 font-semibold transition"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div x-show="peopleTab === 'commissioners'" x-cloak>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
                    @forelse ($commissioners as $person)
                        @php
                            $bioSections = collect($person->data["bio_sections"] ?? [])
                                ->filter(fn ($section) => ! empty($section["heading"]) || ! empty($section["body"]));

                            if ($bioSections->isEmpty() && $person->description) {
                                $bioSections = collect([
                                    [
                                        "heading" => "Professional Background",
                                        "body" => $person->description,
                                    ],
                                    [
                                        "heading" => "Education & Certifications",
                                        "body" => "This profile can be expanded in the CMS with education, certifications, and professional development details.",
                                    ],
                                ]);
                            }
                        @endphp
                        <article class="flex flex-col items-center rounded-lg bg-white p-4 text-center shadow" x-data="{ bioOpen: false }">
                            @if ($person->image_url)
                                <img src="{{ $person->image_url }}?v={{ $person->updated_at->timestamp }}" class="mb-4 h-40 w-32 rounded-md object-cover transition duration-300 hover:scale-105" alt="{{ $person->alt_text ?: $person->title }}">
                            @endif
                            <h3 class="text-lg font-bold">{{ $person->title }}</h3>
                            <p class="text-sm text-gray-600">{{ $person->subtitle }}</p>
                            @if ($bioSections->isNotEmpty())
                                <button
                                    type="button"
                                    @click="bioOpen = true"
                                    class="mt-3 text-sm font-semibold text-green-700 hover:underline"
                                >
                                    View Biography
                                </button>
                            @endif

                            @if ($bioSections->isNotEmpty())
                                <div
                                    x-show="bioOpen"
                                    x-cloak
                                    x-transition.opacity.duration.300ms
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm"
                                    @click.self="bioOpen = false"
                                    @keydown.escape.window="bioOpen = false"
                                >
                                    <div
                                        x-show="bioOpen"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave="transition ease-in duration-200"
                                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                                        class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-xl"
                                    >
                                        <button type="button" @click="bioOpen = false" class="absolute right-4 top-4 text-gray-400 transition hover:text-gray-600" aria-label="Close biography">
                                            <i class="fa-solid fa-xmark text-xl"></i>
                                        </button>
                                        <div class="mb-6 text-center">
                                            @if ($person->image_url)
                                                <img src="{{ $person->image_url }}?v={{ $person->updated_at->timestamp }}" class="mx-auto mb-3 h-24 w-24 rounded-full object-cover" alt="{{ $person->alt_text ?: $person->title }}">
                                            @endif
                                            <h3 class="text-xl font-bold">{{ $person->title }}</h3>
                                            <p class="text-gray-600">{{ $person->subtitle }}</p>
                                        </div>
                                        <div class="space-y-5 text-gray-700">
                                            @foreach ($bioSections as $section)
                                                <div>
                                                    @if (! empty($section["heading"]))
                                                        <h4 class="mb-2 font-semibold text-green-700">{{ $section["heading"] }}</h4>
                                                    @endif
                                                    @if (! empty($section["body"]))
                                                        <div class="space-y-3 text-sm leading-6">
                                                            @foreach (preg_split("/\n\s*\n/", $section["body"]) as $paragraph)
                                                                <p>{{ $paragraph }}</p>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">No commissioners have been published yet.</p>
                    @endforelse
                </div>
            </div>

            <div x-show="peopleTab === 'exOfficials'" x-cloak>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($exOfficials as $person)
                        <article class="flex flex-col items-center rounded-lg bg-white p-4 text-center shadow">
                            @if ($person->image_url)
                                <img src="{{ $person->image_url }}?v={{ $person->updated_at->timestamp }}" class="mb-4 h-40 w-32 rounded-md object-cover transition duration-300 hover:scale-105" alt="{{ $person->alt_text ?: $person->title }}">
                            @endif
                            <h3 class="text-lg font-bold">{{ $person->title }}</h3>
                            <p class="text-sm text-gray-600">{{ $person->subtitle }}</p>
                        </article>
                    @empty
                        <p class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">No ex-officials have been published yet.</p>
                    @endforelse
                </div>
            </div>

            <div x-show="peopleTab === 'management'" x-cloak>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @forelse ($leadership as $person)
                        <article class="rounded-lg bg-white p-5 text-center shadow">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-700">
                                <i class="fa-solid fa-user-tie text-xl"></i>
                            </div>
                            <h3 class="text-lg font-bold">{{ $person->title }}</h3>
                            @if ($person->description)
                                <p class="mt-2 text-sm leading-6 text-gray-600">{{ $person->description }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">No management roles have been published yet.</p>
                    @endforelse
                </div>
            </div>

            @if ($executive || $managers->isNotEmpty())
                <div class="mt-12 rounded-lg bg-white px-5 py-8 shadow-sm">
                    <h2 class="mb-8 text-center text-2xl font-bold text-slate-950">Organogram</h2>
                    <div class="flex flex-col items-center">
                        @if ($executive)
                            <div class="rounded-lg bg-green-600 px-6 py-3 text-center font-bold text-white shadow">
                                {{ $executive->title }}
                            </div>
                        @endif
                        @if ($managers->isNotEmpty())
                            <div class="h-12 w-1 bg-green-600"></div>
                            <div class="flex flex-wrap justify-center gap-5 md:gap-12">
                                @foreach ($managers as $manager)
                                    <div class="rounded-lg bg-green-600 px-6 py-3 text-center font-bold text-white shadow">
                                        {{ $manager->title }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($stats->isNotEmpty())
        <section class="px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 text-center md:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <h3 class="text-2xl font-bold text-green-700">{{ $stat->title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $stat->description }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col justify-center gap-4 md:flex-row">
            <a href="{{ route("childrens-corner") }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-6 py-3 text-center font-bold uppercase text-gray-800 transition hover:border-green-700 hover:text-green-700">
                <i class="fa-solid fa-child mr-2 text-red-600"></i>
                Child Rights Corner
            </a>
            <a href="{{ route("reporting") }}" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-6 py-3 text-center font-bold uppercase text-white transition hover:bg-red-700">
                Report a Case Now
            </a>
        </div>
    </section>
@endsection

@extends("layouts.app")

@section("title", "About Us")

@section("content")
    @php
        $cards = $cmsCards ?? collect();
        $hero = $cmsHero ?? null;
        $stats = $cmsContent->get("stat", collect()) ?? collect();
        $storyCards = $cards->whereIn("category", ["Mandate, Mission & Objectives", "Mandate, Mission & Vision"]);

        $findStory = function (array $slugs) use ($storyCards, $cards) {
            foreach ($slugs as $slug) {
                $match = $storyCards->firstWhere("slug", $slug) ?? $cards->firstWhere("slug", $slug);

                if ($match) {
                    return $match;
                }
            }

            return null;
        };

        $storyTabs = [
            "mandate" => [
                "label" => "Our Mandate",
                "icon" => "fa-shield-halved",
                "item" => $findStory(["our-mandate"]),
            ],
            "mission" => [
                "label" => "Our Mission",
                "icon" => "fa-scale-balanced",
                "item" => $findStory(["our-mission"]),
            ],
            "objectives" => [
                "label" => "Our Objectives",
                "icon" => "fa-bullseye",
                "item" => $findStory(["our-objectives", "our-vision"]),
            ],
        ];

        // Bullets come from the CMS (data.bullets) so the copy stays editable.
        // Fall back to one bullet per line in the card description.
        $storyBullets = function ($item): array {
            if (! $item) {
                return [];
            }

            $bullets = collect(data_get($item->data, "bullets", []))
                ->map(fn ($bullet) => trim((string) $bullet))
                ->filter()
                ->values();

            if ($bullets->isEmpty() && $item->description) {
                $bullets = collect(preg_split("/\r\n|\r|\n/", $item->description))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values();
            }

            return $bullets->all();
        };

        $backgrounds = collect($storyTabs)
            ->map(fn ($tab) => $tab["item"]?->image_url ? $tab["item"]->image_url . "?v=" . $tab["item"]->updated_at->timestamp : null)
            ->filter()
            ->all();
        $commissioners = $cards->where("category", "Commissioners")->values();
        $leadership = $cards->where("category", "Organogram")->values();
        $executive = $leadership->firstWhere("slug", "chief-executive-officer") ?? $leadership->first();
        $managers = $leadership->reject(fn ($item) => $executive && $item->is($executive))->values();
        $exOfficials = $cards->where("category", "Ex-Officials")->values();
    @endphp

    <section
        class="relative overflow-hidden rounded-lg"
        x-data="{
            tab: 'mandate',
            keys: ['mandate', 'mission', 'objectives'],
            move(offset) {
                const index = this.keys.indexOf(this.tab);
                const key = this.keys[(index + offset + this.keys.length) % this.keys.length];
                this.tab = key;
                this.$nextTick(() => document.querySelector('[data-story-tab=' + key + ']')?.focus());
            }
        }"
    >
        <div class="absolute inset-0">
            @if ($hero?->image_url)
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $hero->image_url }}?v={{ $hero->updated_at->timestamp }}')"></div>
            @endif
            @foreach ($storyTabs as $key => $tab)
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

        <div class="relative z-10 px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-3xl text-white">
                    <p class="text-sm font-semibold uppercase tracking-wide text-green-100">{{ $hero?->subtitle ?: "Championing the Rights of Children in Malawi" }}</p>
                    <h1 class="mt-3 text-3xl font-bold md:text-5xl">{{ $hero?->title ?: "About Us" }}</h1>
                    @if ($hero?->description)
                        <p class="mt-4 max-w-3xl text-base leading-7 text-white/90">{{ $hero->description }}</p>
                    @endif
                </div>

                <div class="mt-8 overflow-hidden rounded-xl border border-white/30 bg-white/95 shadow-xl backdrop-blur">
                    <div
                        class="border-b border-slate-200 bg-slate-50/90 px-3 pt-3 sm:px-6"
                        role="tablist"
                        aria-label="Mandate, mission and objectives"
                        @keydown.right.prevent="move(1)"
                        @keydown.left.prevent="move(-1)"
                    >
                        <div class="grid grid-cols-3 gap-1 sm:flex sm:justify-center sm:gap-2">
                            @foreach ($storyTabs as $key => $tab)
                                <button
                                    type="button"
                                    role="tab"
                                    id="story-tab-{{ $key }}"
                                    data-story-tab="{{ $key }}"
                                    aria-controls="story-panel-{{ $key }}"
                                    :aria-selected="tab === '{{ $key }}'"
                                    :tabindex="tab === '{{ $key }}' ? 0 : -1"
                                    @click="tab = '{{ $key }}'"
                                    :class="tab === '{{ $key }}' ? 'bg-green-700 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-green-50 hover:text-green-800'"
                                    class="w-full rounded-lg px-1 py-2.5 text-[13px] leading-tight font-semibold whitespace-nowrap transition sm:w-auto sm:px-6 sm:text-base"
                                >
                                    <span class="hidden sm:inline-block"><i class="fa-solid {{ $tab["icon"] }} sm:mr-2"></i></span>
                                    {{ $tab["label"] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="px-4 py-6 sm:px-6 sm:py-8">
                        @foreach ($storyTabs as $key => $tab)
                            @php
                                $bullets = $storyBullets($tab["item"]);
                            @endphp
                            <div
                                id="story-panel-{{ $key }}"
                                role="tabpanel"
                                aria-labelledby="story-tab-{{ $key }}"
                                x-show="tab === '{{ $key }}'"
                                x-cloak
                                x-transition:enter="transition ease-out duration-300 motion-reduce:transition-none"
                                x-transition:enter-start="opacity-0 translate-y-2 motion-reduce:translate-y-0"
                                x-transition:enter-end="opacity-100 translate-y-0"
                            >
                                @if (filled($bullets))
                                    <ul class="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4">
                                        @foreach ($bullets as $bullet)
                                            <li class="flex gap-3 rounded-xl border border-slate-200 bg-slate-50/80 p-4 transition duration-300 hover:-translate-y-0.5 hover:border-green-600/40 hover:bg-white hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none sm:gap-4 sm:p-5">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-700 text-xs font-bold text-white tabular-nums sm:h-9 sm:w-9">
                                                    {{ $loop->iteration }}
                                                </span>
                                                <p class="text-sm leading-6 text-slate-700 sm:text-base sm:leading-7">{{ $bullet }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="rounded-xl bg-slate-50 p-6 text-slate-600">Content for this section will be published shortly.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-12 sm:px-6 lg:px-8" x-data="{ peopleTab: 'commissioners' }">
        <div class="mx-auto max-w-7xl">
            @php
                $peopleTabs = ["commissioners" => "Commissioners", "exOfficials" => "Ex-Officials", "management" => "Management"];
            @endphp
            <div class="mb-6 sm:hidden">
                <label for="people-select" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">View group</label>
                <div class="relative">
                    <select
                        id="people-select"
                        x-model="peopleTab"
                        class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-4 py-3 pr-10 text-sm font-semibold text-slate-800 shadow-sm transition focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100"
                    >
                        @foreach ($peopleTabs as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <i class="fa-solid fa-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                </div>
            </div>

            <div class="mb-6 hidden flex-wrap gap-4 border-b border-slate-200 sm:flex">
                @foreach ($peopleTabs as $key => $label)
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

    @php
        // Cards already rendered by the bespoke sections above. Anything left
        // over is still published CMS content and must not be silently dropped.
        $claimedCardIds = collect($storyTabs)
            ->pluck("item")
            ->filter()
            ->pluck("id")
            ->merge($commissioners->pluck("id"))
            ->merge($leadership->pluck("id"))
            ->merge($exOfficials->pluck("id"));

        $remainingCards = $cards->reject(fn ($card) => $claimedCardIds->contains($card->id))->values();
    @endphp

    @include("partials.cms-public-blocks", ["cmsCards" => $remainingCards])

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

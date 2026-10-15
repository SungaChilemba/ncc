@extends("layouts.app")

@section("title", "Children's Corner")

@section("content")
    @php
        $hero = $cmsHero ?? null;
        $activities = ($cmsCards ?? collect())->where("category", "Activities")->values();
        $rights = ($cmsCards ?? collect())->where("category", "Child Rights")->values();
        $updates = $cmsNews ?? collect();
        $gallery = $cmsGallery ?? collect();
        $resources = $cmsResources ?? collect();
        $ctas = $cmsCtas ?? collect();

        $heroImage = $hero?->image_url
            ? $hero->image_url . "?v=" . $hero->updated_at->timestamp
            : asset("images/hero-children.jpg");

        $galleryItems = $gallery
            ->filter(fn ($image) => $image->image_url)
            ->map(fn ($image) => [
                "src" => $image->image_url . "?v=" . $image->updated_at->timestamp,
                "alt" => $image->alt_text ?: $image->title ?: "Children gallery image",
            ])
            ->values();

        // In-page anchor navigation only lists sections that have published content.
        $navItems = collect([
            ["id" => "activities", "label" => "Activities", "visible" => $activities->isNotEmpty()],
            ["id" => "child-rights", "label" => "Child Rights", "visible" => $rights->isNotEmpty()],
            ["id" => "gallery", "label" => "Gallery", "visible" => $galleryItems->isNotEmpty()],
            ["id" => "news", "label" => "News & Updates", "visible" => $updates->isNotEmpty()],
            ["id" => "resources", "label" => "Resources", "visible" => $resources->isNotEmpty()],
        ])->filter(fn ($item) => $item["visible"])->values();
    @endphp

    {{-- Hero: full-bleed image with the CMS copy overlaid on top of it. --}}
    <section class="relative -mx-4 overflow-hidden bg-white sm:-mx-6 lg:-mx-8">
        <img src="{{ $heroImage }}"
             alt="{{ $hero?->alt_text ?: $hero?->title ?: "Children at an NCC activity" }}"
             class="absolute inset-0 h-full w-full object-cover object-top"
             fetchpriority="high" />

        {{-- White fade keeps the centered dark copy legible wherever the image sits behind it. --}}
        <div class="absolute inset-0 bg-linear-to-b from-white/85 via-white/75 to-white/85"></div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 pt-12 pb-14 sm:px-6 lg:px-8">
            <div class="reveal mx-auto max-w-xl text-center lg:max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-700">
                    For every child in Malawi
                </p>
                <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">
                    {{ $hero?->title ?: "Child Rights Corner" }}
                </h1>
                @if ($hero?->description)
                    <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-600">
                        {{ $hero->description }}
                    </p>
                @endif

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route("reporting") }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-urgent-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-urgent-700 focus:outline-none focus:ring-2 focus:ring-urgent-600 focus:ring-offset-2 sm:w-auto">
                        <i class="fa-solid fa-shield-halved"></i>
                        Report a Case Now
                    </a>
                    @if ($navItems->isNotEmpty())
                        <a href="#{{ $navItems->first()["id"] }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:border-brand-600 hover:text-brand-700 sm:w-auto">
                            Explore
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Sticky in-page anchor navigation (Alpine scrollspy). --}}
    @if ($navItems->isNotEmpty())
        <nav class="sticky top-[var(--site-header-h)] z-30 -mx-4 border-b border-slate-200 bg-white/90 backdrop-blur sm:-mx-6 lg:-mx-8"
             aria-label="Children's Corner sections"
             x-data="{
                 active: '{{ $navItems->first()["id"] }}',
                 ids: {{ \Illuminate\Support\Js::from($navItems->pluck("id")->all()) }},
                 init() {
                     const observer = new IntersectionObserver((entries) => {
                         entries.forEach((entry) => {
                             if (entry.isIntersecting) this.active = entry.target.id;
                         });
                     }, { rootMargin: '-45% 0px -50% 0px' });
                     this.ids.forEach((id) => {
                         const el = document.getElementById(id);
                         if (el) observer.observe(el);
                     });
                 },
             }">
            <div class="mx-auto max-w-7xl overflow-x-auto px-4 sm:px-6 lg:px-8">
                <div class="flex gap-1 py-2">
                    @foreach ($navItems as $item)
                        <a href="#{{ $item["id"] }}"
                           @click="active = '{{ $item["id"] }}'"
                           :class="active === '{{ $item["id"] }}'
                               ? 'bg-brand-700 text-white shadow-sm'
                               : 'text-slate-600 hover:bg-brand-50 hover:text-brand-700'"
                           class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition">
                            {{ $item["label"] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    @endif

    {{-- Activities: modern card grid driven by CMS "Activities" cards. --}}
    @if ($activities->isNotEmpty())
        <section id="activities" class="-mx-4 bg-white px-4 py-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <span class="inline-block h-1 w-10 rounded-full bg-urgent-600"></span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Fun Ways to Learn and Grow
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        Games, stories, and activities that help you understand your rights and have fun while learning.
                    </p>
                </div>

                @php
                    // Playful per-card accent gradients (cycled); written out literally so Tailwind detects them.
                    $activityAccents = [
                        "from-amber-400 to-orange-500 shadow-amber-500/40",
                        "from-sky-400 to-blue-500 shadow-sky-500/40",
                        "from-rose-400 to-pink-500 shadow-rose-500/40",
                        "from-violet-400 to-purple-500 shadow-violet-500/40",
                        "from-emerald-400 to-teal-500 shadow-emerald-500/40",
                        "from-cyan-400 to-sky-500 shadow-cyan-500/40",
                        "from-fuchsia-400 to-purple-600 shadow-fuchsia-500/40",
                    ];
                @endphp

                <div class="mt-12 grid gap-5 sm:grid-cols-2 md:grid-cols-3">
                    @foreach ($activities as $activity)
                        {{-- Outer wrapper owns the scroll-reveal transform; the inner card owns the hover lift. --}}
                        <div class="reveal">
                            <div class="group flex h-full items-center gap-4 overflow-hidden rounded-2xl border border-slate-200 bg-linear-to-br from-white to-slate-50 p-4 shadow-sm transition duration-300 hover:-translate-y-1.5 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-600/15 lg:gap-5 lg:p-5">
                                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-lg transition duration-300 group-hover:-rotate-3 group-hover:scale-110 group-hover:shadow-xl lg:h-[4.5rem] lg:w-[4.5rem] {{ $activityAccents[$loop->index % count($activityAccents)] }}">
                                    <i class="{{ $activity->subtitle ?: "fa-solid fa-star" }} text-2xl lg:text-3xl" aria-hidden="true"></i>
                                </span>
                                <h3 class="min-w-0 flex-1 font-playful text-xl leading-tight font-semibold tracking-wide text-slate-900 transition duration-300 group-hover:text-brand-700 lg:text-2xl">
                                    {{ $activity->title }}
                                </h3>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Child Rights: numbered grid with a dark spotlight intro cell. --}}
    @if ($rights->isNotEmpty())
        <section id="child-rights" class="-mx-4 bg-slate-50 px-4 py-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <span class="inline-block h-1 w-10 rounded-full bg-urgent-600"></span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Know Your Rights
                    </h2>
                </div>

                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="reveal rounded-2xl bg-brand-900 p-8 text-white sm:col-span-2 lg:col-span-1">
                        <i class="fa-solid fa-shield-halved text-2xl text-brand-200"></i>
                        <h3 class="mt-4 text-xl font-bold">Every right, for every child</h3>
                        <p class="mt-3 text-sm leading-6 text-brand-100">
                            Awareness of child rights also encourages families, schools, and communities to create a
                            safe and supportive environment where every child can grow, learn, and reach their full
                            potential.
                        </p>
                    </div>

                    @foreach ($rights as $right)
                        <div class="reveal group rounded-2xl border border-slate-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg">
                            <div class="flex items-start gap-4">
                                <span class="text-3xl font-bold tabular-nums text-brand-200 transition group-hover:text-brand-600">
                                    {{ str_pad((string) $loop->iteration, 2, "0", STR_PAD_LEFT) }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 text-brand-700">
                                        <i class="{{ $right->subtitle ?: "fa-solid fa-shield" }}"></i>
                                        <h3 class="text-base font-bold text-slate-900">
                                            {{ $right->title }}
                                        </h3>
                                    </div>
                                    @if ($right->description)
                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            {{ $right->description }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Gallery: bento grid with Alpine-powered lightbox. --}}
    @if ($galleryItems->isNotEmpty())
        <section id="gallery"
                 class="-mx-4 bg-white px-4 py-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
                 x-data="{
                     open: false,
                     index: 0,
                     images: {{ \Illuminate\Support\Js::from($galleryItems) }},
                     next() { this.index = (this.index + 1) % this.images.length; },
                     prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; },
                 }"
                 @keydown.escape.window="open = false"
                 @keydown.arrow-right.window="open && next()"
                 @keydown.arrow-left.window="open && prev()">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <span class="inline-block h-1 w-10 rounded-full bg-urgent-600"></span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Helping Kids Grow Through Play and Learning
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        Moments from our programs, events, and community activities across Malawi.
                    </p>
                </div>

                <div class="mt-12 grid auto-rows-[10rem] grid-cols-2 gap-4 md:auto-rows-[13rem] md:grid-cols-4">
                    @foreach ($galleryItems as $i => $image)
                        <button type="button"
                                @click="index = {{ $i }}; open = true"
                                class="reveal group relative overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-slate-900/5 transition hover:ring-2 hover:ring-brand-600 {{ $i === 0 ? "col-span-2 row-span-2" : "" }}"
                                aria-label="View image: {{ $image["alt"] }}">
                            <img src="{{ $image["src"] }}"
                                 alt="{{ $image["alt"] }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            <span class="absolute inset-0 flex items-center justify-center bg-brand-900/0 text-white opacity-0 transition duration-300 group-hover:bg-brand-900/30 group-hover:opacity-100">
                                <i class="fa-solid fa-magnifying-glass-plus text-xl"></i>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Lightbox --}}
            <div x-show="open"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Image viewer">
                <button type="button"
                        @click="open = false"
                        class="absolute top-4 right-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                        aria-label="Close image viewer">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>

                <button type="button"
                        @click="prev()"
                        class="absolute left-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                        aria-label="Previous image">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <img :src="images[index].src"
                     :alt="images[index].alt"
                     class="max-h-[80vh] max-w-full rounded-2xl object-contain shadow-2xl" />

                <button type="button"
                        @click="next()"
                        class="absolute right-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                        aria-label="Next image">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <p class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-4 py-1 text-sm text-white"
                   x-text="`${index + 1} / ${images.length}`"></p>
            </div>
        </section>
    @endif

    {{-- News & Updates: consistent grid cards linking to canonical article pages. --}}
    @if ($updates->isNotEmpty())
        <section id="news" class="-mx-4 bg-slate-50 px-4 py-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <span class="inline-block h-1 w-10 rounded-full bg-urgent-600"></span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Kids News &amp; Updates
                    </h2>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        The latest stories, programs, and announcements for children and families.
                    </p>
                </div>

                <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($updates as $update)
                        @php
                            $url = route("news.show", ["slug" => $update->slug]);
                            $image = $update->image_url
                                ? $update->image_url . "?v=" . $update->updated_at->timestamp
                                : asset("images/news1.png");
                        @endphp
                        <article class="reveal group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">
                            <a href="{{ $url }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
                                <img src="{{ $image }}"
                                     alt="{{ $update->alt_text ?: $update->title }}"
                                     loading="lazy"
                                     class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105" />
                            </a>
                            <div class="flex flex-1 flex-col p-6">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                                    @if ($update->published_on)
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fa-regular fa-calendar text-slate-400"></i>
                                            {{ $update->published_on->format("d M Y") }}
                                        </span>
                                    @endif
                                    @if ($update->location)
                                        <span class="inline-flex items-center gap-1">
                                            <i class="fa-solid fa-location-dot text-slate-400"></i>
                                            {{ strtoupper($update->location) }}
                                        </span>
                                    @endif
                                    @if ($update->category)
                                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-brand-700">
                                            {{ $update->category }}
                                        </span>
                                    @endif
                                </div>

                                <h3 class="mt-3 line-clamp-2 text-lg font-bold leading-snug text-slate-900">
                                    <a href="{{ $url }}" class="transition group-hover:text-brand-700">
                                        {{ $update->title }}
                                    </a>
                                </h3>

                                @if ($update->description)
                                    <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">
                                        {{ $update->description }}
                                    </p>
                                @endif

                                <a href="{{ $url }}"
                                   class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-700 transition group-hover:gap-2.5">
                                    {{ $update->button_text ?: "Read More" }}
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Resources: downloadable documents from CMS "Resource" blocks. --}}
    @if ($resources->isNotEmpty())
        <section id="resources" class="-mx-4 bg-white px-4 py-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="reveal mx-auto max-w-2xl text-center">
                    <span class="inline-block h-1 w-10 rounded-full bg-urgent-600"></span>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        Resources for Children
                    </h2>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($resources as $resource)
                        @php
                            $resourceUrl = $resource->button_link ?: $resource->public_url;
                        @endphp
                        <div class="reveal flex flex-col rounded-2xl border border-slate-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-lg">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                <i class="fa-solid fa-file-lines text-lg"></i>
                            </div>
                            @if ($resource->category)
                                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-brand-700">
                                    {{ $resource->category }}
                                </p>
                            @endif
                            <h3 class="mt-1 text-base font-bold text-slate-900">
                                {{ $resource->title }}
                            </h3>
                            @if ($resource->description)
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    {{ $resource->description }}
                                </p>
                            @endif
                            @if ($resourceUrl && $resourceUrl !== "#")
                                <a href="{{ $resourceUrl }}"
                                   class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition hover:gap-2.5">
                                    {{ $resource->button_text ?: "View Resource" }}
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Call to action: CMS CTAs when published, always ending with the core action path. --}}
    <section class="-mx-4 px-4 pb-16 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="reveal mx-auto max-w-7xl overflow-hidden rounded-3xl bg-brand-900 px-6 py-14 text-center shadow-xl sm:px-12">
            @if ($ctas->isNotEmpty())
                @php $cta = $ctas->first(); @endphp
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    {{ $cta->title }}
                </h2>
                @if ($cta->description)
                    <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-brand-100">
                        {{ $cta->description }}
                    </p>
                @endif
            @else
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Protect a Child Today
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-brand-100">
                    Your voice can change a child's life. Report violations, support our work, and help build a
                    Malawi where every child grows up safe and empowered.
                </p>
            @endif

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @if ($ctas->isNotEmpty() && $ctas->first()->button_link && $ctas->first()->button_link !== "#")
                    <a href="{{ $ctas->first()->button_link }}"
                       class="inline-flex w-full items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50 sm:w-auto">
                        {{ $ctas->first()->button_text }}
                    </a>
                @endif
                <a href="{{ route("reporting") }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-urgent-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-urgent-700 sm:w-auto">
                    <i class="fa-solid fa-shield-halved"></i>
                    Report a Case Now
                </a>
                <a href="{{ route("donate") }}"
                   class="inline-flex w-full items-center justify-center rounded-full border border-white/30 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                    Donate
                </a>
            </div>
        </div>
    </section>
@endsection

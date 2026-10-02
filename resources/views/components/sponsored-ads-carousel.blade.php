@php
    $slides = collect($ads ?? [])
        ->map(function ($ad) {
            $isModel = $ad instanceof \App\Models\SiteContent;

            return [
                'title' => $isModel ? $ad->title : ($ad['title'] ?? ''),
                'description' => $isModel ? $ad->description : ($ad['description'] ?? ''),
                'image' => $isModel
                    ? ($ad->image_url ? $ad->image_url . '?v=' . $ad->updated_at->timestamp : asset('images/ad-promo-3.png'))
                    : ($ad['image'] ?? asset('images/ad-promo-3.png')),
                'is_featured' => $isModel ? (bool) data_get($ad->data, 'is_featured') : (bool) ($ad['is_featured'] ?? false),
                'ends_at' => $isModel ? (data_get($ad->data, 'ends_at') ?? '') : ($ad['ends_at'] ?? ''),
                'url' => $isModel ? ($ad->button_link ?: route('advertise')) : ($ad['url'] ?? route('advertise')),
            ];
        })
        ->values();

    // Local fallback for the img onerror handler, so a broken upload degrades to a real
    // local image instead of an unreachable third-party placeholder service.
    $fallbackImage = asset('images/ad-promo-3.png');
@endphp

@if ($slides->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        activeSlide: 0,
        totalSlides: {{ $slides->count() }},
        perView() {
            return window.innerWidth >= 1024 ? 3 : (window.innerWidth >= 768 ? 2 : 1);
        },
        get maxSlide() {
            return Math.max(0, this.totalSlides - this.perView());
        },
        next() {
            this.activeSlide = this.activeSlide >= this.maxSlide ? 0 : this.activeSlide + 1;
        },
        prev() {
            this.activeSlide = this.activeSlide <= 0 ? this.maxSlide : this.activeSlide - 1;
        }
    }" @resize.window="activeSlide = Math.min(activeSlide, maxSlide)">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-2">
                <span class="h-2.5 w-2.5 rounded-full bg-orange-500 inline-block animate-pulse"></span>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Sponsored Ads</h2>
            </div>
            @if ($showViewAll ?? false)
                <a href="{{ route('advertise') }}" class="text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors flex items-center gap-1">
                    View all
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            @endif
        </div>

        <!-- Ad Slider Container -->
        <div class="overflow-hidden">
            <div class="flex transition-transform duration-300 ease-in-out"
                 :style="`transform: translateX(-${activeSlide * (100 / perView())}%)`">
                @foreach ($slides as $ad)
                    <div class="w-full md:w-1/2 lg:w-1/3 shrink-0 px-3">
                        <article class="bg-white rounded-xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col">
                            <a href="{{ $ad['url'] }}" class="flex group flex-1 p-4 gap-4">
                                <!-- Image Thumbnail -->
                                <div class="w-24 h-24 sm:w-28 sm:h-28 shrink-0 rounded-lg overflow-hidden bg-slate-100 border border-slate-100">
                                    <img src="{{ $ad['image'] }}" alt="{{ $ad['title'] }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='{{ $fallbackImage }}'" />
                                </div>

                                <!-- Details -->
                                <div class="flex-1 flex flex-col justify-between">
                                    <div>
                                        <!-- Badges -->
                                        <div class="flex items-center gap-2 text-xs mb-1.5 flex-wrap">
                                            @if ($ad['is_featured'])
                                                <span class="inline-flex items-center gap-1 font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded text-[11px]">
                                                    <svg class="w-3 h-3 fill-amber-500" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                    FEATURED
                                                </span>
                                            @endif
                                            @if ($ad['ends_at'])
                                                <span class="text-slate-400 text-[11px]">Ends {{ $ad['ends_at'] }}</span>
                                            @endif
                                        </div>

                                        <!-- Title -->
                                        <h3 class="font-bold text-slate-800 text-sm leading-snug group-hover:text-orange-600 transition-colors line-clamp-2">
                                            {{ $ad['title'] }}
                                        </h3>

                                        <!-- Description -->
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                            {{ $ad['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Carousel Controls (Left Arrow, Dots, Right Arrow) -->
        <div class="flex items-center justify-center gap-3 mt-6">
            <!-- Previous Button -->
            <button @click="prev()"
                    class="w-8 h-8 rounded-full border border-slate-200 bg-white text-slate-400 hover:text-slate-600 hover:border-slate-300 flex items-center justify-center transition-colors shadow-sm focus:outline-none"
                    aria-label="Previous slide">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Pagination Dots -->
            <div class="flex items-center gap-1.5">
                <template x-for="i in (totalSlides - perView() + 1)" :key="i">
                    <button @click="activeSlide = i - 1"
                            :class="activeSlide === (i - 1) ? 'w-4 bg-orange-500' : 'w-2 bg-slate-200 hover:bg-slate-300'"
                            class="h-2 rounded-full transition-all duration-200 focus:outline-none"></button>
                </template>
            </div>

            <!-- Next Button -->
            <button @click="next()"
                    class="w-8 h-8 rounded-full border border-slate-200 bg-white text-slate-400 hover:text-slate-600 hover:border-slate-300 flex items-center justify-center transition-colors shadow-sm focus:outline-none"
                    aria-label="Next slide">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </section>
@endif
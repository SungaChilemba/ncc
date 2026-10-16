@extends("layouts.app")

@section("title", "Latest News")

@section("content")
@php
    use Illuminate\Support\Str;
    $articles = $articles ?? collect();
    $heroSlides = $heroSlides ?? collect();
    $featuredArticle = $featuredArticle ?? $articles->first();
    $categoriesCollection = $categories ?? $articles->pluck('category')->filter()->unique()->values();

    $slides = $heroSlides->map(function ($a) {
        return [
            'tag' => 'NEWS',
            'category' => $a->category ?: 'Headlines',
            'title' => $a->title,
            'image' => $a->image_url ? $a->image_url . '?v=' . $a->updated_at->timestamp : asset('images/LATEST1.jpg'),
            'link' => route('news.show', ['slug' => $a->slug]),
        ];
    })->values();

    // Fallback to latest articles if heroSlides empty but articles exist
    if ($slides->isEmpty() && $articles->isNotEmpty()) {
        $slides = $articles->take(3)->map(function ($a) {
            return [
                'tag' => 'NEWS',
                'category' => $a->category ?: 'Headlines',
                'title' => $a->title,
                'image' => $a->image_url ? $a->image_url . '?v=' . $a->updated_at->timestamp : asset('images/LATEST1.jpg'),
                'link' => route('news.show', ['slug' => $a->slug]),
            ];
        })->values();
    }

    $categoriesForAlpine = $categoriesCollection->map(function ($c) {
        return ['value' => Str::slug($c), 'label' => strtoupper($c)];
    })->values();

    $firstCategory = $categoriesForAlpine->first()['value'] ?? '';

    $newsItemsForAlpine = $articles->map(function ($a) {
        return [
            'category' => Str::slug($a->category ?? 'general'),
            'categoryLabel' => strtoupper($a->category ?? 'GENERAL'),
            'date' => $a->published_on ? $a->published_on->format('d M Y') : ($a->updated_at ? $a->updated_at->format('d M Y') : ''),
            'location' => $a->location ? strtoupper($a->location) : '',
            'title' => $a->title,
            'excerpt' => str($a->description ?? '')->squish()->limit(150)->toString(),
            'image' => $a->image_url ? $a->image_url . '?v=' . $a->updated_at->timestamp : asset('images/news1.png'),
            'link' => route('news.show', ['slug' => $a->slug]),
        ];
    })->values();
@endphp
				<!--hero section-->
				<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 ">
								<!-- Carousel & Hero Container -->
								<div x-data="{
		    activeSlide: 0,
		    slides: @js($slides),
		    next() {
		        this.activeSlide = (this.activeSlide + 1) % this.slides.length;
		    },
		    prev() {
		        this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length;
		    }
		}"
												class="relative rounded-xl overflow-hidden shadow-md group bg-gray-900 h-104 md:h-124 min-h-104">
												<template x-if="slides.length === 0">
																<div class="absolute inset-0 flex items-center justify-center text-white text-lg font-semibold bg-gray-800">No published news yet.</div>
												</template>
												<!-- Slides Loop -->
												<template x-for="(slide, index) in slides" :key="index">
																<div class="absolute inset-0 w-full h-full transition-opacity duration-700"
																				:class="activeSlide === index ? 'opacity-100 z-10' : 'opacity-0 z-0'">
																				<!-- Background Image & Overlay -->
																				<img :src="slide.image" :alt="slide.title"
																								class="absolute inset-0 w-full h-full object-cover z-0" />
																				<div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/30 to-black/20 z-0"></div>

																				<!-- Category Badge - Top Left -->
																				<div class="absolute top-4 left-4 md:top-6 md:left-6 z-20">
																								<div
																												class="inline-flex items-center gap-1.5 bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-md text-sm font-semibold text-gray-900 shadow-sm">
																												<span x-text="slide.tag"></span>
																												<span class="text-emerald-600 font-bold">&rarr;</span>
																												<span class="text-emerald-600" x-text="slide.category"></span>
																								</div>
																				</div>

																				<!-- Text Overlay -->
																				<div class="relative z-20 flex flex-col items-center justify-end h-full px-6 pb-16 text-center">
																								<a :href="slide.link"
																												class="text-white text-2xl md:text-4xl font-extrabold tracking-tight hover:text-gray-200 transition-colors drop-shadow-md">
																												<span x-text="slide.title"></span>
																												<svg class="inline-block ml-2 w-6 h-6 md:w-8 md:h-8 stroke-current align-middle shrink-0" fill="none" viewBox="0 0 24 24"
																																stroke-width="2.5">
																																<path stroke-linecap="round" stroke-linejoin="round"
																																				d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
																												</svg>
																								</a>
																				</div>

																				<!-- Pagination Dots at Bottom -->
																				<div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2">
																								<template x-for="(dotslide, dotIndex) in slides" :key="dotIndex">
																												<button @click="activeSlide = dotIndex"
																																:class="activeSlide === dotIndex ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/80'"
																																class="h-2.5 rounded-full transition-all duration-300 focus:outline-none"
																																:aria-label="'Go to slide ' + (dotIndex + 1)"></button>
																								</template>
																				</div>
																</div>
												</template>

												<!-- Navigation Arrows -->
												<button @click="prev()"
																class="absolute left-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 rounded-full bg-gray-800/70 text-white flex items-center justify-center hover:bg-gray-900/90 transition-colors focus:outline-none shadow-md"
																aria-label="Previous Slide">
																<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
																				<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
																</svg>
												</button>

												<button @click="next()"
																class="absolute right-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 rounded-full bg-gray-800/70 text-white flex items-center justify-center hover:bg-gray-900/90 transition-colors focus:outline-none shadow-md"
																aria-label="Next Slide">
																<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
																				<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
																</svg>
												</button>
								</div>

								<!-- Promotional Call-to-Action Bar -->
								<div class="mt-6 border-t border-b border-gray-200 py-4 px-2">
												<div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
																<div
																				class="flex items-center gap-4 text-gray-900 font-extrabold text-sm sm:text-base tracking-wide uppercase">
																				<span>PARTNER WITH NCC TO INCREASE YOUR REACH AND IMPACT</span>
																				<span class="hidden md:inline-block text-red-500 font-light text-xl">|</span>
																</div>
																<a href="{{ route('advertise') }}"
																				class="inline-flex items-center gap-2 border-2 border-red-600 text-red-600 hover:bg-red-600 hover:text-white font-bold text-sm px-5 py-2.5 rounded-md transition-colors duration-200 tracking-wider whitespace-nowrap uppercase">
																				<span>ADVERTISE WITH NCC</span>
																				<span class="font-black">&gt;</span>
																</a>
												</div>
								</div>
				</section>

				<!-- Top Divider -->
				<div class="flex justify-center my-6">
								<hr class="w-full border-t-3 border-red-600">
				</div>

				<!-- latest news section -->
				<section class="w-full bg-white py-12 px-4 sm:px-6 lg:px-8">
								<!-- Red Line Divider -->
								<div class="flex justify-center my-6">
												<hr class="w-7xl border-t-3 border-red-600">
								</div>
								<div class="max-w-6xl mx-auto" x-data="{
		    tab: @js($firstCategory),
		    mobileOpen: false,
		    headingsOnly: false,
		    visible: {},
		    categories: @js($categoriesForAlpine),
		    newsItems: @js($newsItemsForAlpine),
		    countFor(value) { return this.newsItems.filter(n => n.category === value).length },
		    shownCount(value) { return Math.min(this.visible[value] || 3, this.countFor(value)) },
		    isShown(item) { return this.newsItems.filter(n => n.category === item.category).indexOf(item) < this.shownCount(item.category) },
		    hasMore(value) { return this.countFor(value) > this.shownCount(value) },
		    labelFor(value) { const c = this.categories.find(c => c.value === value); return c ? c.label : '' },
		    loadMore() { this.visible[this.tab] = this.shownCount(this.tab) + 3 },
		    showLess() { this.visible[this.tab] = 3 }
		}">
												<!-- Heading -->
												<div class="flex justify-between items-center gap-4">
																<h2 class="text-2xl sm:text-3xl font-bold text-red-700 mb-4">
																				LATEST NEWS
																</h2>
																<div class="flex items-center gap-3">
																				<span class="text-sm font-medium text-gray-700">Headings Only</span>
																				<button @click="headingsOnly = !headingsOnly" role="switch" :aria-checked="headingsOnly"
																								class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
																								:class="headingsOnly ? 'bg-red-600' : 'bg-gray-200'">
																								<span class="sr-only">Headings Only</span>
																								<span aria-hidden="true"
																												class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
																												:class="headingsOnly ? 'translate-x-5' : 'translate-x-0'"></span>
																				</button>
																</div>
												</div>

												<!-- Tabbed Filters -->
												<div class="mb-8">
																<!-- Mobile Dropdown -->
																<div class="md:hidden">
																				<button @click="mobileOpen = !mobileOpen"
																								class="w-full flex items-center justify-between bg-white border border-gray-300 rounded-md px-4 py-2 text-gray-700">
																								<span x-text="labelFor(tab)"></span>
																								<i class="fa-solid fa-chevron-down text-xs"></i>
																				</button>
																				<div x-show="mobileOpen" @click.away="mobileOpen = false" x-transition
																								class="mt-2 bg-white border border-gray-200 rounded-md shadow-lg overflow-hidden">
																								<template x-for="option in categories" :key="option.value">
																												<button @click="tab = option.value; mobileOpen = false"
																																:class="tab === option.value ? 'bg-red-600 text-white' : 'text-gray-700 hover:bg-gray-50'"
																																class="flex items-center justify-between w-full text-left px-4 py-3 text-sm font-medium">
																																<span x-text="option.label"></span>
																																<span class="text-xs px-1.5 py-0.5 rounded-full"
																																				:class="tab === option.value ? 'bg-white text-red-600' : 'bg-red-100 text-red-700'"
																																				x-text="countFor(option.value)"></span>
																												</button>
																								</template>
																				</div>
																</div>
																<!-- Desktop Tabs -->
																<div class="hidden md:flex flex-wrap gap-2">
																				<template x-for="option in categories" :key="option.value">
																								<button @click="tab = option.value"
																												:class="tab === option.value ? 'bg-red-600 text-white' :
																												    'bg-white text-gray-700 border hover:border-red-600 hover:text-red-600'"
																												class="inline-flex items-center gap-2 px-4 py-2 rounded-md font-semibold text-sm transition">
																												<span x-text="option.label"></span>
																												<span class="text-xs px-1.5 py-0.5 rounded-full"
																																:class="tab === option.value ? 'bg-white text-red-600' : 'bg-red-100 text-red-700'"
																																x-text="countFor(option.value)"></span>
																								</button>
																				</template>
																</div>
																<p x-show="categories.length === 0" class="text-sm text-gray-500 mt-4">No categories available yet.</p>
												</div>

												<!-- Grid of Cards -->
												<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
																<template x-for="item in newsItems" :key="item.title">
																				<div x-show="item.category === tab && isShown(item)"
																								x-transition:enter="transition ease-out duration-300"
																								x-transition:enter-start="opacity-0 translate-y-2"
																								x-transition:enter-end="opacity-100 translate-y-0"
																								x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
																								x-transition:leave-end="opacity-0"
																								class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition overflow-hidden">
																								<a :href="item.link" class="block" x-show="!headingsOnly">
																												<img :src="item.image" :alt="item.title" class="w-full h-48 object-cover">
																								</a>
																								<div class="p-6">
																												<p class="text-sm text-slate-500 mb-2" x-text="(item.date ? item.date : '') + (item.date && item.location ? ' · ' : '') + item.location"></p>
																												<h3 class="text-lg font-bold leading-snug text-slate-900 mb-3">
																																<a :href="item.link" class="hover:text-brand-700" x-text="item.title"></a>
																												</h3>
																												<p class="text-slate-600 text-sm leading-6" x-text="item.excerpt" x-show="!headingsOnly"></p>
																												<a :href="item.link" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline">Read More</a>
																								</div>
																				</div>
																</template>
												</div>
												<p x-show="newsItems.length === 0" class="col-span-full text-center text-slate-500 py-8">No news articles have been published yet.</p>

												<!-- Load More Button (per category, shown only when more than 3 cards exist) -->
												<div class="mt-8 text-center" x-show="hasMore(tab)" x-transition>
																<button @click="loadMore()"
																				class="bg-brand-700 hover:bg-brand-800 text-white px-6 py-2 rounded-md font-semibold transition duration-200">
																				Load More
																</button>
												</div>
												<!-- Show Less Button -->
												<div class="mt-8 text-center" x-show="shownCount(tab) > 3" x-transition>
																<button @click="showLess()"
																				class="bg-white border border-slate-300 text-slate-700 hover:border-brand-600 hover:text-brand-700 px-6 py-2 rounded-md font-semibold transition duration-200">
																				Show Less
																</button>
												</div>
								</div>
				</section>

				<!-- Sponsored Ads Section — CMS-driven, with demo fallback when no CMS ads exist -->
				@php
					$carouselAds = $sponsoredAds->isNotEmpty()
						? $sponsoredAds
						: collect([
							[
								'title' => 'PROPOSAL WRITING SERVICES',
								'description' => 'We provide proposal writing services across all fields of study.',
								'image' => asset('images/ad-promo-1.jfif'),
								'is_featured' => true,
								'ends_at' => 'Aug 31',
								'url' => route('advertise'),
							],
							[
								'title' => 'HELP SPONSOR OUR CHILD FRIENDLY PROGRAMS...',
								'description' => 'Help us in reaching out to a lot of young people in Malawi....',
								'image' => asset('images/ad-promo-2.jfif'),
								'is_featured' => true,
								'ends_at' => 'Dec 31',
								'url' => route('advertise'),
							],
							[
								'title' => 'APPLY TO BE THE NEXT CHILD COMMISSIONER',
								'description' => 'This is your chance to be the change maker that the youth needs.',
								'image' => asset('images/ad-promo-3.png'),
								'is_featured' => true,
								'ends_at' => 'Dec 31',
								'url' => route('advertise'),
							],
						]);
				@endphp

				<x-sponsored-ads-carousel :ads="$carouselAds" :show-view-all="true" />
				<!-- Featured News Section -->
				@if($featuredArticle)
				<section class="w-full bg-white mx-auto px-4 sm:px-6 lg:px-8 py-6">
								<div class="relative h-112 md:h-136 min-h-112 max-w-7xl mx-auto rounded-2xl overflow-hidden shadow-lg group bg-gray-900 flex flex-col justify-between p-6 md:p-10">
												@php
																$featuredImage = $featuredArticle->image_url ? $featuredArticle->image_url . '?v=' . $featuredArticle->updated_at->timestamp : asset('images/EBOLA.webp');
																$featuredLink = route('news.show', ['slug' => $featuredArticle->slug]);
																$featuredSource = data_get($featuredArticle->data, 'source.name') ?: ($featuredArticle->category ?: 'NCC');
												@endphp
												<!-- Background Image -->
												<img src="{{ $featuredImage }}" alt="{{ $featuredArticle->title }}"
																class="absolute inset-0 w-full h-full object-cover z-0 transition-transform duration-500 group-hover:scale-105"
																onerror="this.onerror=null; this.src='{{ asset('images/EBOLA.webp') }}'" />

												<!-- Dark Gradient Overlay for Readability -->
												<div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/30 to-black/20 z-0"></div>

												<!-- Top Left Badge -->
												<div class="relative z-10 self-start">
																<div
																				class="inline-flex items-center gap-2 bg-white/95 backdrop-blur-sm px-4 py-2 rounded-lg text-sm font-black tracking-wider text-gray-900 shadow-md">
																				<span>FEATURED NEWS</span>
																				<span class="text-red-600 font-bold">&rarr;</span>
																				<span class="text-red-600 uppercase">{{ $featuredArticle->category ?: 'News' }}</span>
																</div>
												</div>

												<!-- Bottom Area: Title Overlay & Source Tag -->
												<div class="relative z-10 w-full flex flex-col md:flex-row md:items-end justify-between gap-6">
																<!-- Headline Link -->
																<div class="max-w-3xl">
																				<a href="{{ $featuredLink }}"
																								class="text-white text-2xl sm:text-3xl md:text-4xl font-black uppercase tracking-tight hover:text-gray-200 transition-colors drop-shadow-lg leading-tight">
																								<span>{{ $featuredArticle->title }}</span>
																								<svg class="inline-block ml-2 w-6 h-6 md:w-8 md:h-8 stroke-current align-middle shrink-0" fill="none"
																												viewBox="0 0 24 24" stroke-width="3">
																												<path stroke-linecap="round" stroke-linejoin="round"
																																d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
																								</svg>
																				</a>
																</div>

																<!-- Bottom Right Source Attribution Badge -->
																<div class="self-end shrink-0">
																				<div
																								class="inline-flex items-center gap-2 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-md text-xs font-semibold text-gray-800 shadow-md">
																								<span class="text-red-600 font-bold">&amp;</span>
																								<span class="uppercase tracking-wider">SOURCE :</span>
																								<span class="text-gray-600 capitalize">{{ $featuredSource }}</span>
																				</div>
																</div>
												</div>
								</div>
				</section>
				@endif

@endsection

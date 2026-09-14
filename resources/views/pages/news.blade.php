@extends("layouts.app")

@section("title", "News")

@section("content")
				@php
								$suppressCmsBlocks = true;
								$featured = $cmsHero ?? null;
								$articles = ($cmsNews ?? collect())->values();
								$related = $articles->take(3);
								$latest = $articles->slice(3)->values();
								$categories = $articles->pluck("category")->filter()->unique()->values();
								$excerpt = fn($text, $limit = 220) => str($text)->squish()->limit($limit);
				@endphp

				<section class="w-full bg-white">
								<div class="max-w-7xl mx-auto px-6 pt-7 flex items-center gap-10">
												<p class="text-lg font-serif text-red-600 uppercase tracking-widest inline-flex items-center gap-2">
																{{ $featured?->category ?: "News" }} <i class="fa-solid fa-book text-base"></i>
												</p>
												<a href="#" class="text-gray-700 hover:text-red-600 inline-flex items-center gap-1">
																Share <i class="fa-solid fa-share-nodes"></i>
												</a>
								</div>
								<h1 class="max-w-7xl mx-auto px-6 py-7 text-5xl md:text-4xl font-bold text-gray-900 mb-4">
												{{ $featured?->title ?: "News" }}
								</h1>
								@if ($featured?->image_url)
												<div class="max-w-7xl mx-auto px-6 relative">
																<img src="{{ $featured->image_url }}?v={{ $featured->updated_at->timestamp }}" alt="{{ $featured->title }}"
																				class="w-full h-100 md:h-200 object-cover">
																<h3
																				class="absolute bottom-4 left-1/2 -translate-x-1/2 md:bottom-8 md:right-8 md:left-auto md:translate-x-0 bg-white text-gray-900 px-4 py-2 rounded-md font-bold text-sm shadow-md">
																				NEWS
																</h3>
												</div>
								@endif
								<div class="max-w-5xl mx-auto px-6 py-10">
												@if ($featured?->published_on)
																<p class="text-sm text-gray-500 mb-4">{{ $featured->published_on->format("j F Y") }}</p>
												@endif
												@if ($featured?->description)
																@foreach (preg_split("/\n\s*\n/", $featured->description) as $index => $paragraph)
																				<p
																								class="{{ $index === 0 ? "text-lg text-gray-700 leading-relaxed font-bold" : "text-lg text-gray-700 leading-relaxed mt-4" }}">
																								{{ $paragraph }}
																				</p>
																@endforeach
												@endif
								</div>
				</section>
				<!-- Related News Section -->
				<section class="w-full bg-white py-12 px-4 sm:px-6 lg:px-8" x-data="{ headingsOnly: false }">
								<div class="flex justify-center my-6">
												<hr class="w-7xl border-t-3 border-red-600">
								</div>
								<div class="max-w-7xl mx-auto space-y-8">
												<div class="flex justify-between items-center gap-4">
																<h2 class="text-3xl md:text-3xl font-bold text-red-700">
																				RELATED NEWS
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

												@forelse ($related as $article)
																<article class="flex flex-col md:flex-row bg-white rounded-lg shadow overflow-hidden">
																				@if ($article->image_url)
																								<img src="{{ $article->image_url }}?v={{ $article->updated_at->timestamp }}"
																												alt="{{ $article->title }}" class="w-full md:w-1/2 h-64 object-cover" x-show="!headingsOnly">
																				@endif
																				<div class="p-6 flex-1">
																								<p class="text-sm text-gray-500 mb-2">
																												{{ $article->published_on?->format("d M Y") }}
																												@if ($article->location)
																																- {{ $article->location }}
																												@endif
																								</p>
																								<h3 class="text-xl font-bold text-gray-900 mb-4">{{ $article->title }}</h3>
																								@if ($article->description)
																												<p class="text-gray-700 leading-7" x-show="!headingsOnly">
																																{{ $excerpt($article->description, 260) }}</p>
																								@endif
																								<a href="{{ route("news.show", $article->slug) }}"
																												class="mt-4 inline-flex text-sm font-semibold text-green-700 hover:underline">Read More</a>
																				</div>
																</article>
												@empty
																<p class="rounded-lg border border-slate-200 bg-white p-8 text-center text-gray-500">No related news has
																				been published yet.</p>
												@endforelse
								</div>
				</section>
				<!-- Latest News Section -->
				<section class="w-full bg-white py-12 px-4 sm:px-6 lg:px-8">
								<div class="flex justify-center my-6">
												<hr class="w-7xl border-t-3 border-red-600">
								</div>
								<div class="max-w-6xl mx-auto" x-data="{ showMoreNews: false, tab: 'All', mobileOpen: false, headingsOnly: false, tabCounts: { 'All': {{ $latest->count() }}, @foreach($categories as $c)'{{ $c }}': {{ $latest->where('category', $c)->count() }},@endforeach } }">
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

												<div class="mb-8">
																<div class="md:hidden">
																				<button @click="mobileOpen = !mobileOpen"
																								class="w-full flex items-center justify-between bg-white border border-gray-300 rounded-md px-4 py-2 text-gray-700">
																								<span x-text="tab"></span>
																								<i class="fa-solid fa-chevron-down text-xs"></i>
																				</button>
																				<div x-show="mobileOpen" @click.away="mobileOpen = false" x-transition
																								class="mt-2 bg-white border border-gray-200 rounded-md shadow-lg overflow-hidden">
																				<button @click="tab = 'All'; mobileOpen = false; showMoreNews = false"
																												:class="tab === 'All' ? 'bg-red-600 text-white' : 'text-gray-700 hover:bg-gray-50'"
																												class="block w-full text-left px-4 py-3 text-sm font-medium">ALL</button>
																				@foreach ($categories as $category)
																												<button @click="tab = @js($category); mobileOpen = false; showMoreNews = false"
																																:class="tab === @js($category) ? 'bg-red-600 text-white' :
																																    'text-gray-700 hover:bg-gray-50'"
																																class="block w-full text-left px-4 py-3 text-sm font-medium">{{ strtoupper($category) }}</button>
																				@endforeach
																				</div>
																</div>
																<div class="hidden md:flex flex-wrap gap-2">
																				<button @click="tab = 'All'; showMoreNews = false"
																								:class="tab === 'All' ? 'bg-red-600 text-white' :
																								    'bg-white text-gray-700 border hover:border-red-600 hover:text-red-600'"
																								class="px-4 py-2 rounded-md font-semibold text-sm transition">ALL</button>
																				@foreach ($categories as $category)
																								<button @click="tab = @js($category); showMoreNews = false"
																												:class="tab === @js($category) ? 'bg-red-600 text-white' :
																												    'bg-white text-gray-700 border hover:border-red-600 hover:text-red-600'"
																												class="px-4 py-2 rounded-md font-semibold text-sm transition">{{ strtoupper($category) }}</button>
																				@endforeach
																</div>
												</div>

												<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
																@forelse ($latest as $index => $article)
																				<article class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden"
																								x-show="({{ $index }} < 3 || showMoreNews) && (tab === 'All' || tab === @js($article->category))">
																								@if ($article->image_url)
																												<img src="{{ $article->image_url }}?v={{ $article->updated_at->timestamp }}"
																																alt="{{ $article->title }}" class="w-full h-48 object-cover" x-show="!headingsOnly">
																								@endif
																								<div class="p-6">
																												<p class="text-sm text-gray-500 mb-2">
																																{{ $article->published_on?->format("d M Y") }}
																																@if ($article->location)
																																				- {{ strtoupper($article->location) }}
																																@endif
																												</p>
																												<h3 class="text-lg font-bold text-gray-900 mb-3">{{ $article->title }}</h3>
																												@if ($article->description)
																																<p class="text-gray-700 text-sm leading-6" x-show="!headingsOnly">
																																				{{ $excerpt($article->description, 150) }}</p>
																												@endif
																												<a href="{{ route("news.show", $article->slug) }}"
																																class="mt-4 inline-flex text-sm font-semibold text-green-700 hover:underline">Read More</a>
																								</div>
																				</article>
																@empty
																				<p class="col-span-full rounded-lg border border-slate-200 bg-white p-8 text-center text-gray-500">No
																								latest news has been published yet.</p>
																@endforelse
												</div>

												<div class="mt-8 text-center" x-show="!showMoreNews && (tabCounts[tab] || 0) > 3" x-cloak>
																<button @click="showMoreNews = true"
																				class="bg-green-700 hover:bg-green-800 text-white px-6 py-2 rounded-md font-semibold transition duration-200">
																				Load More
																</button>
												</div>
												<div class="mt-8 text-center" x-show="showMoreNews && (tabCounts[tab] || 0) > 3" x-cloak>
																<button @click="showMoreNews = false"
																				class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-md font-semibold transition duration-200">
																				Show Less
																</button>
												</div>
								</div>
				</section>

				<section class="w-full bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
								<div class="max-w-5xl mx-auto text-center">
												<h2 class="text-2xl sm:text-3xl font-bold text-green-700 mb-4">
																Subscribe to Get Our Latest News
												</h2>
												<p class="text-gray-700 mb-6">
																Stay updated with the latest stories, programs, and impact reports from the National Children's Commission.
												</p>
												<form action="#" method="POST" class="flex flex-col sm:flex-row gap-4 justify-center">
																<input type="email" name="email" placeholder="Enter your email"
																				class="w-full sm:w-2/3 px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-600">
																<button type="submit"
																				class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">
																				Subscribe
																</button>
												</form>
								</div>
				</section>
@endsection

@extends("layouts.app")

@section("title", "Home")

@section("content")
				@php
								$heroSlides = collect([
												[
																"type" => "hero",
																"image" => $heroImage ?: asset("images/NCChero.jpg"),
																"fallback" => asset("images/NCChero.jpg"),
																"alt" => $hero?->title,
																"eyebrow" => $hero?->subtitle,
																"title" => $hero?->title ?: "Our Children, Our Responsibility",
																"description" => $hero?->description,
																"button_url" => $hero?->button_url ?: route("reporting"),
																"button_text" => $hero?->button_text ?: "Report a Case Now",
												],
												[
																"type" => "slide",
																"image" => asset("images/nccNews1.jpg"),
																"fallback" => asset("images/nccNews1.jpg"),
																"alt" => "NCC News",
																"eyebrow" => null,
																"title" => "Our Children, Our Responsibility",
																"description" => "Safeguarding children’s rights and dignity.",
																"button_url" => route("reporting"),
																"button_text" => "Report a Case Now",
												],
												[
																"type" => "slide",
																"image" => asset("images/nccCeleb.jpg"),
																"fallback" => asset("images/nccCeleb.jpg"),
																"alt" => "NCC Celebrations",
																"eyebrow" => null,
																"title" => "Our Children, Our Responsibility",
																"description" => "Safeguarding children’s rights and dignity.",
																"button_url" => route("reporting"),
																"button_text" => "Report a Case Now",
												],
								]);
				@endphp
				<!-- Hero Section -->
				@if ($heroSlides->isNotEmpty())
				<section
								x-data="{
												active: 0,
												total: {{ $heroSlides->count() }},
												paused: false,
												timer: null,
												reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
												init() {
																if (!this.reduced) this.start();
												},
												start() {
																if (this.total < 2 || this.reduced) return;
																this.stop();
																this.timer = setInterval(() => { if (!this.paused) this.next(); }, 6000);
												},
												stop() {
																if (this.timer) { clearInterval(this.timer); this.timer = null; }
												},
												next() { this.active = (this.active + 1) % this.total; },
												prev() { this.active = (this.active - 1 + this.total) % this.total; }
								}"
								@mouseenter="paused = true" @mouseleave="paused = false"
								@focusin="paused = true" @focusout="paused = false"
								class="relative h-[70vh] w-full overflow-hidden bg-slate-900"
								role="region" aria-roledescription="carousel" aria-label="National Children's Commission"
				>
								@foreach ($heroSlides as $index => $slide)
												<div
																class="absolute inset-0 transition-opacity duration-700 ease-out motion-reduce:transition-none"
																:class="active === {{ $index }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
																:style="reduced ? 'transition-duration: 0ms' : ''"
																:aria-hidden="active === {{ $index }} ? 'false' : 'true'"
																role="group" aria-roledescription="slide"
																aria-label="{{ $index + 1 }} of {{ $heroSlides->count() }}"
												>
																<img src="{{ $slide['image'] }}" alt="{{ $slide['alt'] ?? '' }}"
																				class="absolute inset-0 h-full w-full object-cover"
																				loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
																				onerror="this.onerror=null; this.src='{{ $slide['fallback'] }}'">
																<div class="absolute inset-0 bg-black/50"></div>

																<div class="relative z-10 flex h-full items-center justify-center px-4 text-center text-white">
																				<div>
																								@if (!empty($slide['eyebrow']))
																												<p class="text-xs font-bold uppercase tracking-[0.2em] text-yellow-400 sm:text-sm mb-2">{{ $slide['eyebrow'] }}</p>
																								@endif
																								@if (!empty($slide['title']))
																												<h1 class="text-3xl md:text-5xl font-bold">{{ $slide['title'] }}</h1>
																								@endif
																								@if (!empty($slide['description']))
																												<p class="mt-4 text-lg md:text-xl">{{ $slide['description'] }}</p>
																								@endif
																								<div class="mt-6 flex flex-col md:flex-row gap-4 justify-center">
																												<a href="{{ $slide['button_url'] }}"
																																class="bg-urgent-600 hover:bg-urgent-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">
																																{{ $slide['button_text'] }}
																												</a>
																												<a href="{{ route("what-we-do") }}" class="border border-white px-4 py-2 rounded-lg font-semibold text-sm">
																																Work With Us
																												</a>
																								</div>
																				</div>
																</div>
												</div>
								@endforeach

								@if ($heroSlides->count() > 1)
												<button type="button" @click="prev()" aria-label="Previous slide"
																class="absolute left-4 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white transition hover:bg-black/60 focus:outline-none focus:ring-2 focus:ring-white">
																<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
																				<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
																</svg>
												</button>
												<button type="button" @click="next()" aria-label="Next slide"
																class="absolute right-4 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/40 text-white transition hover:bg-black/60 focus:outline-none focus:ring-2 focus:ring-white">
																<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
																				<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
																</svg>
												</button>

												<div class="absolute bottom-6 left-1/2 z-30 flex -translate-x-1/2 gap-2">
																@foreach ($heroSlides as $index => $slide)
																				<button type="button" @click="active = {{ $index }}"
																				:aria-current="active === {{ $index }} ? 'true' : 'false'"
																				aria-label="Go to slide {{ $index + 1 }}"
																				:class="active === {{ $index }} ? 'w-8 bg-white' : 'w-3 bg-white/50 hover:bg-white/80'"
																				class="h-3 rounded-full transition-all duration-300 focus:outline-none"></button>
																@endforeach
												</div>
								@endif
				</section>
				@endif

				<!-- Mission & Impact Section -->
				<section class="py-16 px-6 md:px-12 bg-white">
								<!-- Top Divider -->
								<div class="flex justify-center my-6">
												<hr class="w-full border-t-2 border-urgent-600">
								</div>

								<!-- Two Column Layout with Right Stretched -->
								<div class="max-w-8xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-20">

												<!-- Left: Programs Section (1/3 width) -->
												<div class="md:col-span-1">
																<h2 class="text-2xl sm:text-3xl font-bold text-brand-800 mb-6">
																				OUR PROGRAMS
																</h2>
																<ul class="space-y-3 text-slate-900 text-lg">
																				<li class="hover:underline">Advocacy & Policy</li>
																				<li class="hover:underline">Awareness</li>
																				<li class="hover:underline">Protection</li>
																				<li class="hover:underline">Referral & Support</li>
																				<li class="hover:underline">Capacity Building</li>
																				<li class="hover:underline">Research</li>
																</ul>
												</div>

												<!-- Right: Mission Content (2/3 width) -->
												<div class="md:col-span-2">
																<h2 class="text-3xl md:text-4xl font-bold text-urgent-700 mb-4">
																				NATIONAL CHILDREN'S COMMISSION
																</h2>
																<h3 class="text-2xl font-bold text-slate-800 mb-4">
																				Championing the Rights of Children in Malawi
																</h3>
																<p class="text-slate-700 text-left mb-2">
																				The National Children's Commission (NCC) was established in 2019 by an Act of Parliament and became
																				fully operational in 2024. It was created to coordinate, monitor, and strengthen accountability in all
																				matters
																				concerning children's rights and welfare in Malawi.
																</p>
																<p class="text-slate-700 text-left mb-2">
																				With its motto "Our Children, Our Responsibility," the Commission works to safeguard children through
																				education, healthcare, protection from abuse, and policy advocacy. Today, the NCC stands as a unifying
																				body ensuring that every child in Malawi grows up safe, empowered, and supported by both government and
																				society.
																</p>
																<p class="text-slate-700 text-left mb-2">
																				Through its initiatives, the NCC has made significant strides in promoting child welfare, including
																				launching awareness campaigns, collaborating with NGOs, and implementing programs that directly impact
																				the lives of children across the nation.
																</p>
																<p class="text-urgent-700 max-w-6xl text-left font-bold mt-4">
																				As the NCC continues to expand its reach and influence, it remains committed to its mission of ensuring
																				that all children in Malawi have the opportunity to thrive in a safe and nurturing environment.<br>
																				Donate today to help us continue our vital work in protecting and empowering the children of Malawi.
																				<a href="{{ route("donate") }}" class="text-urgent-600 hover:underline font-semibold">Donate</a>
																</p>
												</div>
								</div>

								<!-- Bottom Divider -->
								<div class="flex justify-center my-6">
												<hr class="w-full border-t-2 border-urgent-600">
								</div>
				</section>

				<!-- Red Line Divider -->

				<!-- Initiatives Section -->
				<section class="py-6 px-6 md:px-12 bg-white">
								<div class="flex justify-left">
												<hr class="w-8 border-t-4 border-urgent-800">
								</div>
								<h2 class="text-xl md:text-xl font-bold text-brand-800 mb-6">TOP STORIES</h2>
								<div class="max-w-8xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">

												<!-- Left Panel: Image + Red Rectangle -->
												@if ($featuredUpdate)
																<div class="md:col-span-2 flex flex-col md:flex-row rounded-lg overflow-hidden shadow-lg">
																				<!-- Image side -->
																				<div class="flex-1">
																								<img src="{{ $featuredUpdate->image_path ? $featuredUpdate->image_url . "?v=" . $featuredUpdate->updated_at->timestamp : asset("images/vaccine.jpg") }}"
																												alt="{{ $featuredUpdate->title }}" class="w-full h-full object-cover">
																				</div>
																				<!-- Red rectangle side -->
																				<div
																								class="bg-{{ $featuredUpdate->accent_color ?? "red" }}-600 text-white flex flex-col justify-between p-4 md:w-1/2">
																								<!-- Icon -->
																								<div class="flex justify-start mb-4">
																												<i class="fa-solid fa-kit-medical text-2xl"></i>
																								</div>
																								<!-- Heading + Text -->
																								<div>
																												<h3 class="text-2xl font-bold mb-2">{{ $featuredUpdate->title }}</h3>
																												<p class="text-sm md:text-base mb-4">
																																{{ $featuredUpdate->description }}
																												</p>
																								</div>
																								<!-- Read More -->
																								@if ($featuredUpdate->button_text)
																												<a href="{{ $featuredUpdate->button_link && $featuredUpdate->button_link !== "#" ? $featuredUpdate->button_link : route('news.show', ['slug' => $featuredUpdate->slug]) }}"
																				class="underline text-sm hover:text-slate-200">{{ $featuredUpdate->button_text }}</a>
																								@endif
																				</div>
																</div>
												@else
																<div class="md:col-span-2 flex flex-col md:flex-row rounded-lg overflow-hidden shadow-lg">
																				<!-- Image side -->
																				<div class="flex-1">
																								<img src="{{ asset("images/NCCkids.jpg") }}" alt="Take on Typhoid"
																												class="w-full h-full object-cover">
																				</div>
																				<!-- Red rectangle side -->
																				<div class="bg-urgent-600 text-white flex flex-col justify-between p-4 md:w-1/2">
																								<!-- Icon -->
																								<div class="flex justify-start mb-4">
																												<i class="fa-solid fa-kit-medical text-2xl"></i>
																								</div>
																								<!-- Heading + Text -->
																								<div>
																												<h3 class="text-2xl font-bold mb-2">PROTECT FAMILIES & CHILDREN</h3>
																												<p class="text-sm md:text-base mb-4">
																																NCC joined other stakeholders in commemorating the international Day of Families & the
																																international Day of street connected children.
																												</p>
																								</div>
																								<!-- Read More -->
																								<a href="https://www.facebook.com/61578135073164/
posts/122177408528937835/?
mibextid=rS40aB7S9Ucbxw6v"
																												class="underline text-sm hover:text-slate-200">Read More</a>
																				</div>
																</div>
												@endif

												<!-- Right Panel: White Card -->
												@if ($sidebarUpdate->isNotEmpty())
																@php $s = $sidebarUpdate->first(); @endphp
																<div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
																				<!-- Image -->
																				<img src="{{ $s->image_path ? $s->image_url . "?v=" . $s->updated_at->timestamp : asset("images/Kids-Coding.jpg") }}"
																								alt="{{ $s->title }}" class="w-full h-48 object-cover">
																				<!-- Text -->
																				<div class="p-6 flex flex-col justify-between flex-1">
																								<div>
																												<h3 class="text-2xl font-bold mb-2">{{ $s->title }}</h3>
																												<p class="text-sm md:text-base mb-4">
																																{{ $s->description }}
																												</p>
																								</div>
																								@if ($s->button_text)
																												<a href="{{ $s->button_link && $s->button_link !== "#" ? $s->button_link : route('news.show', ['slug' => $s->slug]) }}"
																																class="underline text-sm text-brand-700 hover:text-brand-800">{{ $s->button_text }}</a>
																								@endif
																				</div>
																</div>
												@else
																<div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
																				<!-- Image -->
																				<img src="{{ asset("images/mwambo.jpg") }}" alt="Children in Tech" class="w-full h-48 object-cover">
																				<!-- Text -->
																				<div class="p-6 flex flex-col justify-between flex-1">
																								<div>
																												<h3 class="text-2xl font-bold mb-2">PROTECT FAMILIES & CHILDREN</h3>
																												<p class="text-sm md:text-base mb-4">
																																The National Children’s Commission joined the Ministry of Gender, Children, Disability and
																																Social Welfare, development partners, traditional leaders, and community members in
																																commemorating the International Day of Families and the International Day of
																																Street-Connected Children at Lunzu Primary School Ground in Blantyre.
																												</p>
																								</div>
																								<!-- Read More -->
																								<a href="https://www.facebook.com/61578135073164/
posts/122177408528937835/?
mibextid=rS40aB7S9Ucbxw6v"
																												class="underline text-sm text-brand-700 hover:text-brand-800">Read More</a>
																				</div>
																</div>
												@endif

								</div>
				</section>

				<!-- Latest News Section -->
				<section class="py-12 px-6 md:px-12 bg-white">
								<div class="max-w-8xl mx-auto" x-data="{ showMoreNews: false }">
												<div class="flex justify-left">
																<hr class="w-8 border-t-4 border-urgent-800">
												</div>
												<h2 class="text-xl md:text-xl font-bold text-brand-800 mb-6">LATEST NEWS</h2>
												<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
																@forelse(($latestNews ?? collect())->take(3) as $article)
																				<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">
																								<a href="{{ $article->public_url }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
																												<img src="{{ $article->image_path ? $article->image_url . '?v=' . $article->updated_at->timestamp : asset('images/news1.png') }}" alt="{{ $article->alt_text ?: $article->title }}" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105">
																								</a>
																								<div class="flex flex-1 flex-col p-6">
																												<div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
																																@if ($article->published_on)
																																				<span class="inline-flex items-center gap-1">
																																								<i class="fa-regular fa-calendar text-slate-400"></i>
																																								{{ $article->published_on->format('d M Y') }}
																																				</span>
																																@endif
																																@if ($article->location)
																																				<span class="inline-flex items-center gap-1">
																																								<i class="fa-solid fa-location-dot text-slate-400"></i>
																																								{{ strtoupper($article->location) }}
																																				</span>
																																@endif
																																@if (!empty($article->category))
																																				<span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-brand-700">
																																								{{ $article->category }}
																																				</span>
																																@endif
																												</div>
																												<h3 class="mt-3 line-clamp-2 text-lg font-bold leading-snug text-slate-900">
																																<a href="{{ $article->public_url }}" class="transition group-hover:text-brand-700">{{ $article->title }}</a>
																												</h3>
																												@if ($article->description)
																																<p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->description }}</p>
																												@endif
																												<a href="{{ $article->public_url }}" class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-700 transition group-hover:gap-2.5">Read More <i class="fa-solid fa-arrow-right text-xs"></i></a>
																								</div>
																				</article>
																@empty
																				<div class="col-span-full rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
																								No news articles have been published yet.
																				</div>
																@endforelse
												</div>

												@if (($latestNews ?? collect())->count() > 3)
																<div x-show="showMoreNews" class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-8">
																				@foreach(($latestNews ?? collect())->slice(3) as $article)
																								<article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:shadow-lg">
																												<a href="{{ $article->public_url }}" class="block overflow-hidden" tabindex="-1" aria-hidden="true">
																																<img src="{{ $article->image_path ? $article->image_url . '?v=' . $article->updated_at->timestamp : asset('images/news1.png') }}" alt="{{ $article->alt_text ?: $article->title }}" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105">
																												</a>
																												<div class="flex flex-1 flex-col p-6">
																																<div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
																																				@if ($article->published_on)
																																								<span class="inline-flex items-center gap-1">
																																												<i class="fa-regular fa-calendar text-slate-400"></i>
																																												{{ $article->published_on->format('d M Y') }}
																																								</span>
																																				@endif
																																				@if ($article->location)
																																								<span class="inline-flex items-center gap-1">
																																												<i class="fa-solid fa-location-dot text-slate-400"></i>
																																												{{ strtoupper($article->location) }}
																																								</span>
																																				@endif
																																				@if (!empty($article->category))
																																								<span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-brand-700">
																																												{{ $article->category }}
																																								</span>
																																				@endif
																																</div>
																																<h3 class="mt-3 line-clamp-2 text-lg font-bold leading-snug text-slate-900">
																																				<a href="{{ $article->public_url }}" class="transition group-hover:text-brand-700">{{ $article->title }}</a>
																																</h3>
																																@if ($article->description)
																																				<p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->description }}</p>
																																@endif
																																<a href="{{ $article->public_url }}" class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-brand-700 transition group-hover:gap-2.5">Read More <i class="fa-solid fa-arrow-right text-xs"></i></a>
																												</div>
																								</article>
																				@endforeach
																</div>
																<div class="mt-8 text-center" x-show="!showMoreNews">
																				<button @click="showMoreNews = true" class="bg-brand-700 hover:bg-brand-800 text-white px-6 py-2 rounded-md font-semibold transition duration-200">Load More</button>
																</div>
																<div class="mt-8 text-center" x-show="showMoreNews">
																				<button @click="showMoreNews = false" class="bg-white border border-slate-300 text-slate-700 hover:border-brand-600 hover:text-brand-700 px-6 py-2 rounded-md font-semibold transition duration-200">Show Less</button>
																</div>
												@endif
								</div>
				</section>
<!-- moments that matter section -->
				<section class="py-12 px-6 md:px-12 bg-white">

								<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-6">
												@forelse($news as $article)
																<article class="overflow-hidden bg-white shadow rounded-xl">
																				<a href="{{ route("news.show", ['slug' => $article->slug]) }}" class="block">
																								<img src="{{ $article->image_path ? $article->image_url . "?v=" . $article->updated_at->timestamp : asset("images/update-1.jpg") }}"
																												alt="{{ $article->alt_text ?: $article->title }}" class="w-full h-48 object-cover rounded-t-xl">
																				</a>
																				<div class="p-6">
																								<a href="{{ route("news.show", ['slug' => $article->slug]) }}" class="font-semibold text-xl hover:text-brand-700">{{ $article->title }}</a>
																				</div>
																</article>
												@empty
																<article class="overflow-hidden bg-white shadow rounded-xl">
																				<img src="{{ asset("images/nccNews1.jpg") }}" alt="Child protection act"
																								class="w-full h-48 object-cover rounded-t-xl">
																				<div class="p-6">
																								<h3 class="font-semibold text-xl">On Friday, 5 june – Ncc engaged the Leaders of the flea Market in
																												Lilongwe in efforts to promote child protection.
																								</h3>
																				</div>
																</article>
																<article class="overflow-hidden bg-white shadow rounded-xl">
																				<img src="{{ asset("images/update-2.jpg") }}" alt="National day of the African child"
																								class="w-full h-48 object-cover rounded-t-xl">
																				<div class="p-6">
																								<h3 class="font-semibold text-xl">May 2025 – National Day of the African Child – Celebrations &
																												Pledges
																								</h3>
																				</div>
																</article>

																<article class="overflow-hidden bg-white shadow rounded-xl">
																				<img src="{{ asset("images/update-2.jpg") }}" alt="National day of the African child"
																								class="w-full h-48 object-cover rounded-t-xl">
																				<div class="p-6">
																								<h3 class="font-semibold text-xl">May 2025 – National Day of the African Child – Celebrations &
																												Pledges
																								</h3>
																				</div>
																</article>
												@endforelse
								</div>
				</section>
				<!-- Action Buttons Section -->
				<section class="py-12 px-6 md:px-12">
								<div class="flex flex-col md:flex-row justify-center gap-4">
												<!-- Child Rights Corner Button -->
												<a href="{{ route("childrens-corner") }}"
																class="flex items-center justify-center border border-slate-300 px-6 py-3 rounded-lg font-bold uppercase text-slate-800 hover:text-brand-700 hover:border-brand-600">
																<i class="fa-solid fa-child mr-2 text-urgent-600"></i>
																CHILD RIGHTS CORNER
												</a>

												<!-- Report a Case Button -->
												<a href="{{ route("reporting") }}"
																class="bg-urgent-600 hover:bg-urgent-700 text-white px-6 py-3 rounded-lg font-bold uppercase text-center">
																REPORT A CASE NOW
												</a>
								</div>

								<!-- Statistics -->
								<div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center mt-12">
												<div>
																<h3 class="text-2xl font-bold text-brand-700">1,249+</h3>
																<p class="text-slate-700">Children Supported</p>
												</div>
												<div>
																<h3 class="text-2xl font-bold text-brand-700">2,000+</h3>
																<p class="text-slate-700">Families Reached</p>
												</div>
												<div>
																<h3 class="text-2xl font-bold text-brand-700">6,000+</h3>
																<p class="text-slate-700">Community Members</p>
												</div>
												<div>
																<h3 class="text-2xl font-bold text-brand-700">28</h3>
																<p class="text-slate-700">Districts Covered</p>
												</div>
								</div>
				</section>

@endsection


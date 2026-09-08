@extends("layouts.app")

@section("title", "Children's Corner")

@section("content")
				@php
								$hero = $cmsHero ?? null;
								$activities = ($cmsCards ?? collect())->where("category", "Activities")->values();
								$rights = ($cmsCards ?? collect())->where("category", "Child Rights")->values();
								$updates = $cmsNews ?? collect();
								$gallery = $cmsGallery ?? collect();
				@endphp

				<section class="bg-white px-4 py-12 text-center">
								<h1 class="text-4xl md:text-6xl font-bold text-green-800">{{ $hero?->title ?: "Child Rights Corner" }}</h1>
								@if ($hero?->description)
												<p class="max-w-xl mx-auto mt-4 text-base md:text-lg">{{ $hero->description }}</p>
								@endif
								@if ($hero?->image_url)
												<img src="{{ $hero->image_url }}?v={{ $hero->updated_at->timestamp }}" alt="{{ $hero->alt_text ?: $hero->title }}" class="mx-auto mt-8 w-70 md:w-96">
								@endif
								<div class="mt-10 flex flex-wrap justify-center gap-8">
												@foreach ($activities as $activity)
																<div class="flex flex-col items-center">
																				<div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-700 shadow-sm">
																								<i class="{{ $activity->subtitle ?: "fa-solid fa-star" }} text-lg"></i>
																				</div>
																				<span class="mt-2 text-sm font-semibold">{{ $activity->title }}</span>
																</div>
												@endforeach
								</div>
				</section>

				<section class="bg-green-700 px-4 py-12 text-white">
								<h2 class="text-center text-2xl font-bold">CHILD RIGHTS</h2>
								<p class="mx-auto mt-4 mb-12 max-w-3xl text-center text-base font-medium">
												Awareness of child rights also encourages families, schools, and communities to create a safe and supportive environment where every child can grow, learn, and reach their full potential.
								</p>
								<div class="mx-auto flex max-w-5xl flex-wrap justify-center gap-8">
												@foreach ($rights as $right)
																<div class="flex flex-col items-center">
																				<div class="flex h-20 w-20 items-center justify-center rounded-full bg-white text-green-700 shadow-sm">
																								<i class="{{ $right->subtitle ?: "fa-solid fa-shield" }} text-xl"></i>
																				</div>
																				<span class="mt-2 text-sm font-semibold">{{ $right->title }}</span>
																</div>
												@endforeach
								</div>
				</section>

				@if ($gallery->isNotEmpty())
								<section class="bg-white px-4 py-12">
												<h2 class="mb-6 text-center text-2xl font-bold md:text-3xl">Helping Kids Grow Through Play And Learning</h2>
												<div class="mx-auto grid max-w-6xl grid-cols-2 gap-4 md:grid-cols-5">
																@foreach ($gallery as $image)
																				@if ($image->image_url)
																								<img src="{{ $image->image_url }}?v={{ $image->updated_at->timestamp }}" alt="{{ $image->alt_text ?: $image->title ?: "Children gallery image" }}" class="h-32 w-full rounded object-contain">
																				@endif
																@endforeach
												</div>
								</section>
				@endif

				@if ($updates->isNotEmpty())
								<section class="bg-white px-6 py-16 md:px-12">
												<div class="mx-auto max-w-7xl">
																<h2 class="mb-10 text-center text-3xl font-bold text-green-700 md:text-4xl">Kids News & Updates</h2>
																<div class="flex flex-wrap justify-center gap-8">
																				@foreach ($updates as $update)
																								<article class="relative overflow-hidden rounded-lg shadow-lg">
																												@if ($update->image_url)
																																<img src="{{ $update->image_url }}?v={{ $update->updated_at->timestamp }}" alt="{{ $update->alt_text ?: $update->title }}" class="rounded-lg">
																												@endif
																												<div class="absolute inset-0 bg-black/40"></div>
																												<div class="absolute inset-0 z-10 flex flex-col items-center justify-end p-4 text-center">
																																<h3 class="mb-2 text-lg font-bold text-white">{{ $update->title }}</h3>
																																@if ($update->button_text)
																																				<a href="{{ $update->button_link ?: "#" }}" class="rounded-md bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">{{ $update->button_text }}</a>
																																@endif
																												</div>
																								</article>
																				@endforeach
																</div>
												</div>
								</section>
				@endif
@endsection

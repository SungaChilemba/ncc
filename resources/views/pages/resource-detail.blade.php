@extends("layouts.app")

@section("title", $resource->title)

@section("content")
				<section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
								<div class="mx-auto max-w-4xl">
												<a href="{{ route("resources") }}" class="text-sm font-semibold text-green-700 hover:underline">Back to resources</a>
												<div class="mt-6 rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
																@if ($resource->category)
																				<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $resource->category }}</span>
																@endif
																<h1 class="mt-4 text-3xl font-bold text-gray-950">{{ $resource->title }}</h1>
																@if ($resource->description)
																				<p class="mt-4 text-lg leading-8 text-gray-700">{{ $resource->description }}</p>
																@endif
																@if ($resource->file_url && $resource->slug)
																				<div class="mt-8 flex flex-wrap gap-3">
																								<a href="{{ $resource->file_url }}" target="_blank" class="rounded-md bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-700">View Document</a>
																								<a href="{{ route("resources.download", $resource->slug) }}" class="rounded-md border border-emerald-600 px-5 py-3 font-semibold text-emerald-700 hover:bg-emerald-50">Download</a>
																				</div>
																@endif
												</div>
								</div>
				</section>

				@if ($relatedResources->isNotEmpty())
								<section class="bg-white px-4 pb-12 sm:px-6 lg:px-8">
												<div class="mx-auto max-w-6xl">
																<h2 class="mb-6 text-2xl font-bold text-emerald-700">Related Resources</h2>
																<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
																				@foreach ($relatedResources as $related)
																								<a href="{{ route("resources.show", $related->slug) }}" class="block rounded-lg border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md">
																												<p class="text-xs font-semibold uppercase text-emerald-700">{{ $related->category }}</p>
																												<h3 class="mt-2 font-bold text-gray-950">{{ $related->title }}</h3>
																												@if ($related->description)
																																<p class="mt-2 text-sm text-gray-600">{{ $related->description }}</p>
																												@endif
																								</a>
																				@endforeach
																</div>
												</div>
								</section>
				@endif
@endsection

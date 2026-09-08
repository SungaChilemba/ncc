@extends("layouts.app")

@section("title", "Resources")

@section("content")
				@php
								$suppressCmsBlocks = true;
								$resourceHero = $cmsHero ?? null;
								$resources = ($cmsResources ?? collect())->values();
								$categories = $resources->pluck("category")->filter()->unique()->values();
				@endphp

				<div class="max-w-7xl mx-auto px-4 py-10" x-data="{ q: '', category: 'All' }">
								<h1 class="text-3xl font-bold text-emerald-700 mb-6">{{ $resourceHero?->title ?: "Resources" }}</h1>
								<p class="text-gray-600 mb-8">
												{{ $resourceHero?->description ?: "Browse official documents and guidelines. Use the search and filters to quickly find what you need." }}
								</p>

								<div class="flex flex-col mb-8 gap-4">
												<div class="flex-1">
																<input type="text" x-model.debounce.150ms="q" placeholder="Search resources..."
																				class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
												</div>
												<div class="flex flex-wrap gap-3">
																<button type="button" @click="category = 'All'"
																				:class="category === 'All' ? 'bg-emerald-600 text-white border-emerald-600' : 'text-gray-600 hover:bg-emerald-50 hover:text-emerald-700'"
																				class="px-4 py-2 border rounded-lg">
																				All
																</button>
																@foreach ($categories as $category)
																				<button type="button" @click="category = @js($category)"
																								:class="category === @js($category) ? 'bg-emerald-600 text-white border-emerald-600' : 'text-gray-600 hover:bg-emerald-50 hover:text-emerald-700'"
																								class="px-4 py-2 border rounded-lg">
																								{{ $category }}
																				</button>
																@endforeach
												</div>
								</div>

								<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
												@forelse ($resources as $resource)
																<div class="bg-white shadow rounded-lg p-6 flex flex-col"
																				x-show="(category === 'All' || category === @js($resource->category)) && (!q || @js(strtolower($resource->title . ' ' . $resource->description . ' ' . $resource->category)).includes(q.toLowerCase()))">
																				<div class="mb-4 flex items-center justify-between gap-4">
																								@if ($resource->category)
																												<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $resource->category }}</span>
																								@endif
																								<span class="text-xs font-semibold uppercase text-gray-400">Document</span>
																				</div>
																				<h3 class="text-lg font-bold text-gray-800 mb-2">{{ $resource->title }}</h3>
																				@if ($resource->description)
																								<p class="text-gray-600 mb-4">{{ $resource->description }}</p>
																				@endif
																				<div class="mt-auto flex flex-wrap gap-3">
																								@if ($resource->file_url && $resource->slug)
																												<a href="{{ $resource->file_url }}" target="_blank"
																																class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
																																View
																												</a>
																												<a href="{{ route("resources.show", $resource->slug) }}"
																																class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
																																Details
																												</a>
																												<a href="{{ route("resources.download", $resource->slug) }}"
																																class="px-4 py-2 border border-emerald-600 text-emerald-600 rounded-lg hover:bg-emerald-50 transition">
																																{{ $resource->button_text ?: "Download" }}
																												</a>
																								@endif
																				</div>
																</div>
												@empty
																<div class="col-span-full rounded-lg border border-slate-200 bg-white p-8 text-center text-gray-500">
																				No resources have been published yet.
																</div>
												@endforelse
								</div>
				</div>
@endsection

@extends("layouts.app")

@section("title", $article->title)

@section("content")
				@php
								$source = data_get($article->data, "source");
				@endphp

				<article class="bg-white px-4 py-10 sm:px-6 lg:px-8">
								<div class="mx-auto max-w-5xl">
												<a href="{{ route("latest-news") }}" class="text-sm font-semibold text-green-700 hover:underline">Back to Latest News</a>
												<div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-gray-500">
																@if ($article->category)
																				<span class="rounded-full bg-red-50 px-3 py-1 font-semibold text-red-700">{{ $article->category }}</span>
																@endif
																@if ($article->published_on)
																				<span>{{ $article->published_on->format("j F Y") }}</span>
																@endif
																@if ($article->location)
																				<span>{{ $article->location }}</span>
																@endif
												</div>
												<h1 class="mt-4 text-3xl font-bold text-gray-950 md:text-5xl">{{ $article->title }}</h1>
												@if ($article->image_url)
																<img src="{{ $article->image_url }}?v={{ $article->updated_at->timestamp }}" alt="{{ $article->alt_text ?: $article->title }}" class="mt-8 h-96 w-full rounded-lg object-cover shadow">
												@else
																<img src="{{ asset('images/news1.png') }}" alt="{{ $article->title }}" class="mt-8 h-96 w-full rounded-lg object-cover shadow">
												@endif
												@if ($article->description)
																<div class="mt-8 space-y-5 text-lg leading-8 text-gray-700">
																				@foreach (preg_split("/\n\s*\n/", $article->description) as $paragraph)
																								<p>{{ $paragraph }}</p>
																				@endforeach
																</div>
												@endif
												@if (is_array($source) && (! empty($source["name"]) || ! empty($source["url"])))
																<div class="mt-8 rounded-lg border border-emerald-100 bg-emerald-50/70 p-5 text-sm leading-6 text-slate-700">
																				<p class="font-semibold text-emerald-800">Source</p>
																				<p class="mt-1">
																								@if (! empty($source["url"]))
																												<a href="{{ $source["url"] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-700 hover:underline">
																																{{ $source["name"] ?? $source["url"] }}
																												</a>
																								@else
																												{{ $source["name"] }}
																								@endif
																								@if (! empty($source["posted_on"]))
																												<span class="text-slate-500"> · Posted {{ \Illuminate\Support\Carbon::parse($source["posted_on"])->format("j F Y") }}</span>
																								@endif
																				</p>
																</div>
												@endif
								</div>
				</article>

				@if (isset($relatedArticles) && $relatedArticles->isNotEmpty())
								<section class="bg-white px-4 pb-12 sm:px-6 lg:px-8">
												<div class="mx-auto max-w-6xl">
																<h2 class="mb-6 text-2xl font-bold text-red-700">Related News</h2>
																<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
																				@foreach ($relatedArticles as $related)
																								@include('partials.news-related-card', ['article' => $related])
																				@endforeach
																</div>
												</div>
								</section>
				@endif
@endsection

@props(['article'])

@php
    $url = route('news.show', ['slug' => $article->slug]);
@endphp

<a href="{{ $url }}" class="block overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm hover:shadow-md transition">
    @if($article->image_url)
        <img src="{{ $article->image_url }}?v={{ $article->updated_at->timestamp }}" alt="{{ $article->alt_text ?: $article->title }}" class="h-44 w-full object-cover">
    @endif
    <div class="p-4">
        @if($article->category)
            <p class="text-xs font-semibold uppercase text-slate-500">{{ $article->category }}</p>
        @endif
        <h3 class="mt-2 font-bold text-gray-950">{{ $article->title }}</h3>
        @if($article->published_on || $article->location)
            <p class="mt-1 text-xs text-gray-500">{{ $article->published_on?->format('j F Y') }}@if($article->location) · {{ $article->location }}@endif</p>
        @endif
    </div>
</a>

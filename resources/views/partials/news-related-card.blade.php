@props(['article'])

@php
    $url = route('news.show', ['slug' => $article->slug]);
@endphp

<a href="{{ $url }}" class="block overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition">
    @if($article->image_url)
        <img src="{{ $article->image_url }}?v={{ $article->updated_at->timestamp }}" alt="{{ $article->alt_text ?: $article->title }}" class="h-48 w-full object-cover">
    @endif
    <div class="p-4">
        @if($article->category)
            <p class="text-xs font-semibold uppercase tracking-wide text-urgent-600">{{ $article->category }}</p>
        @endif
        <h3 class="mt-2 text-lg font-bold leading-snug text-slate-900">{{ $article->title }}</h3>
        @if($article->published_on || $article->location)
            <p class="mt-1 text-xs text-slate-500">{{ $article->published_on?->format('j F Y') }}@if($article->location) · {{ $article->location }}@endif</p>
        @endif
    </div>
</a>

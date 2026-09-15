@props([
    'article',
    'excerptLimit' => 150,
    'showImage' => true,
    'imageClass' => 'w-full h-48 object-cover',
])

@php
    $url = route('news.show', ['slug' => $article->slug]);
    $image = $article->image_url ? $article->image_url . '?v=' . $article->updated_at->timestamp : asset('images/news1.png');
    $excerpt = str($article->description ?? '')->squish()->limit($excerptLimit);
    $date = $article->published_on?->format('d M Y');
    $location = $article->location;
@endphp

<article class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
    @if($showImage)
        <a href="{{ $url }}" class="block">
            <img src="{{ $image }}" alt="{{ $article->alt_text ?: $article->title }}" class="{{ $imageClass }}">
        </a>
    @endif
    <div class="p-6">
        <p class="text-sm text-gray-500 mb-2">
            @if($date){{ $date }}@endif
            @if($date && $location) · @endif
            @if($location){{ strtoupper($location) }}@endif
            @if($article->category) <span class="ml-2 text-xs font-semibold uppercase text-red-600">{{ $article->category }}</span>@endif
        </p>
        <h3 class="text-lg font-bold text-gray-900 mb-3">
            <a href="{{ $url }}" class="hover:text-red-700">{{ $article->title }}</a>
        </h3>
        @if($excerpt && !$article->trashed ?? true)
            <p class="text-gray-700 text-sm leading-6">{{ $excerpt }}</p>
        @endif
        <a href="{{ $url }}" class="mt-4 inline-flex text-sm font-semibold text-green-700 hover:underline">Read More</a>
    </div>
</article>

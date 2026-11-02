@php
    $hero = $cmsHero ?? null;
    $cards = ($cmsCards ?? collect())->groupBy(fn ($item) => $item->category ?: 'Content');

    // The "Thanks to our supporters" group renders directly below the hero section.
    $cards = $cards->sortBy(fn ($items, $group) => strtolower((string) $group) === 'thanks to our supporters' ? 0 : 1);
    $stats = $cmsContent->get('stat', collect()) ?? collect();
    $ctas = $cmsCtas ?? collect();
@endphp

<section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="relative overflow-hidden rounded-lg bg-green-900 px-6 py-16 text-center text-white shadow">
            @if ($hero?->image_url)
                <img src="{{ $hero->image_url }}?v={{ $hero->updated_at->timestamp }}" alt="{{ $hero->alt_text ?: $hero->title ?: $title }}" class="absolute inset-0 h-full w-full object-cover opacity-45">
            @endif
            <div class="relative z-10">
                <h1 class="text-3xl font-bold md:text-5xl">{{ $hero?->title ?: $title }}</h1>
                @if ($hero?->subtitle)
                    <p class="mx-auto mt-4 max-w-3xl text-lg">{{ $hero->subtitle }}</p>
                @endif
                @if ($hero?->description)
                    <p class="mx-auto mt-4 max-w-4xl leading-7 text-white/90">{{ $hero->description }}</p>
                @endif
            </div>
        </div>

        @if ($stats->isNotEmpty())
            <div class="mt-8 grid grid-cols-2 gap-4 text-center md:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="text-2xl font-bold text-green-700">{{ $stat->title }}</h3>
                        @if ($stat->description)
                            <p class="mt-1 text-sm text-gray-600">{{ $stat->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@foreach ($cards as $group => $items)
    @if (! str_contains(strtolower((string) $group), 'faq'))
        <section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-6 flex items-center gap-3">
                    <span class="h-1 w-10 rounded-full bg-red-700"></span>
                    <h2 class="text-2xl font-bold text-gray-950">{{ $group }}</h2>
                </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($items as $card)
                    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        @if ($card->image_url)
                            <img src="{{ $card->image_url }}?v={{ $card->updated_at->timestamp }}" alt="{{ $card->alt_text ?: $card->title ?: $group }}" class="h-56 w-full object-cover">
                        @endif
                        <div class="p-5">
                            @if ($card->subtitle)
                                <p class="text-xs font-semibold uppercase tracking-wide text-green-700">{{ $card->subtitle }}</p>
                            @endif
                            <h3 class="mt-1 text-lg font-bold text-gray-950">{{ $card->title }}</h3>
                            @if ($card->description)
                                <div class="mt-3 space-y-3 text-sm leading-6 text-gray-600">
                                    @foreach (preg_split("/\n\s*\n/", $card->description) as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach
                                </div>
                            @endif
                            @if (! empty($card->data['items']) && is_array($card->data['items']))
                                <div class="mt-4 space-y-3">
                                    @foreach ($card->data['items'] as $entry)
                                        <div class="rounded-md bg-slate-50 p-3">
                                            @if (! empty($entry['label']))
                                                <p class="text-sm font-semibold text-slate-900">{{ $entry['label'] }}</p>
                                            @endif
                                            @if (! empty($entry['value']))
                                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $entry['value'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @if ($card->button_text && $card->button_link)
                                <a href="{{ $card->button_link }}" class="mt-4 inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">{{ $card->button_text }}</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif
@endforeach

{{-- FAQs render after the other content groups, just before the footer area. --}}
@foreach ($cards as $group => $items)
    @if (str_contains(strtolower((string) $group), 'faq'))
        @include("partials.faq-accordion", [
            "faqs" => $items,
            "title" => $group,
        ])
    @endif
@endforeach

@if ($ctas->isNotEmpty())
    <section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl rounded-lg bg-green-700 px-6 py-10 text-center text-white">
            @foreach ($ctas as $cta)
                <h2 class="text-2xl font-bold">{{ $cta->title }}</h2>
                @if ($cta->description)
                    <p class="mx-auto mt-3 max-w-3xl">{{ $cta->description }}</p>
                @endif
                @if ($cta->button_text && $cta->button_link)
                    <a href="{{ $cta->button_link }}" class="mt-5 inline-flex rounded-md bg-red-600 px-5 py-3 font-semibold text-white hover:bg-red-700">{{ $cta->button_text }}</a>
                @endif
            @endforeach
        </div>
    </section>
@endif

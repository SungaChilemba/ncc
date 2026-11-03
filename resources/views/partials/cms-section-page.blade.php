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
            @php
                $heroImages = [];
                if (is_array($hero?->data['images'] ?? null)) {
                    foreach ($hero->data['images'] as $heroImage) {
                        if (is_string($heroImage) && $heroImage !== '') $heroImages[] = $heroImage;
                    }
                }
                if (empty($heroImages) && $hero?->image_url) $heroImages[] = $hero->image_url;
            @endphp
            @foreach ($heroImages as $index => $heroImageUrl)
                <img src="{{ $heroImageUrl }}" alt="{{ $hero->alt_text ?: $hero->title ?: $title }}" class="absolute inset-0 h-full w-full object-cover opacity-45 transition-opacity duration-1000 {{ $index === 0 ? 'opacity-45' : 'opacity-0' }}" data-hero-slide="{{ $index }}">
            @endforeach
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
    @php
        $watchStoryCard = $items->first();
        $watchSource = $watchStoryCard?->data['youtube_url'] ?? $watchStoryCard?->data['video_url'] ?? $watchStoryCard?->description;
        $watchId = '';
        if ($watchSource) {
            if (preg_match('/[?&]v=([\w-]{6,})/', (string) $watchSource, $watchMatch)) {
                $watchId = $watchMatch[1];
            } elseif (preg_match('/\/(?:embed|shorts|live)\/([\w-]{6,})/', (string) $watchSource, $watchMatch)) {
                $watchId = $watchMatch[1];
            }
        }
        $watchEmbedUrl = $watchId ? 'https://www.youtube.com/embed/'.$watchId : null;
    @endphp
    @if (! str_contains(strtolower((string) $group), 'faq'))
        @if (strtolower((string) $group) === 'watch our story')
            <section class="bg-white px-4 py-12 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <div class="mb-8 text-center">
                        <span class="mx-auto block h-1 w-10 rounded-full bg-red-700"></span>
                        <h2 class="mt-4 text-2xl font-bold text-gray-950">{{ $group }}</h2>
                    </div>
                    @if ($watchEmbedUrl)
                        <div class="relative mx-auto aspect-video w-full max-w-2xl overflow-hidden rounded-lg shadow-sm">
                            <iframe class="absolute inset-0 h-full w-full" src="{{ $watchEmbedUrl }}?rel=0" title="{{ $group }}" frameborder="0" allowfullscreen loading="lazy"></iframe>
                        </div>
                    @endif
                </div>
            </section>
        @else
        @if (strtolower((string) $group) === 'where will your donation go')
            @php
                $donationSegments = [
                    ['label' => 'Direct charitable programmes', 'value' => 75, 'color' => '#16a34a'],
                    ['label' => 'Fundraising', 'value' => 25, 'color' => '#ea580c'],
                ];

                $donationCard = $items->first();
                $donationSource = (string) ($donationCard?->description ?? '');
                if ($donationSource !== '') {
                    preg_match_all('/(\d+(?:\.\d+)?)\s*%/', $donationSource, $donationMatches);
                    $donationValues = array_map('floatval', $donationMatches[1]);
                    if (count($donationValues) >= 2) {
                        $donationSegments = [
                            ['label' => 'Direct charitable programmes', 'value' => $donationValues[0], 'color' => '#16a34a'],
                            ['label' => 'Fundraising', 'value' => $donationValues[1], 'color' => '#ea580c'],
                        ];
                    }
                }

                $donationChartTotal = array_sum(array_column($donationSegments, 'value'));
                $donationChartRadius = 80;
                $donationChartAngle = -90;
                $donationSegmentPaths = [];
                foreach ($donationSegments as $donationSegment) {
                    $donationFraction = $donationSegment['value'] / max($donationChartTotal, 1) * 100;
                    $donationStartAngle = $donationChartAngle;
                    $donationEndAngle = $donationStartAngle + $donationFraction;
                    $donationStartRad = deg2rad($donationStartAngle);
                    $donationEndRad = deg2rad($donationEndAngle);
                    $donationX1 = 100 + $donationChartRadius * cos($donationStartRad);
                    $donationY1 = 100 + $donationChartRadius * sin($donationStartRad);
                    $donationX2 = 100 + $donationChartRadius * cos($donationEndRad);
                    $donationY2 = 100 + $donationChartRadius * sin($donationEndRad);
                    $donationLargeArc = $donationEndAngle - $donationStartAngle > 180 ? 1 : 0;
                    $donationSegmentPaths[] = [
                        'd' => 'M100 100 L'.round($donationX1, 2).' '.round($donationY1, 2).' A80 80 0 '.$donationLargeArc.' 1 '.round($donationX2, 2).' '.round($donationY2, 2).' Z',
                        'color' => $donationSegment['color'],
                        'label' => $donationSegment['label'],
                        'value' => $donationSegment['value'],
                    ];
                    $donationChartAngle = $donationEndAngle;
                }

                $donationLabels = array_map(fn ($donationSegment) => $donationSegment['label'], $donationSegments);
                $donationValues = array_map(fn ($donationSegment) => $donationSegment['value'], $donationSegments);
                $donationColors = array_map(fn ($donationSegment) => $donationSegment['color'], $donationSegments);
                $donationChartConfig = [
                    'labels' => $donationLabels,
                    'datasets' => [
                        [
                            'label' => 'Donation allocation',
                            'data' => $donationValues,
                            'backgroundColor' => $donationColors,
                        ],
                    ],
                ];
            @endphp
            <section class="bg-white px-4 py-12 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-3xl">
                    <div class="mb-8 text-center">
                        <span class="mx-auto block h-1 w-10 rounded-full bg-red-700"></span>
                        <h2 class="mt-4 text-2xl font-bold text-gray-950">{{ $group }}</h2>
                    </div>
                    <div class="mx-auto max-w-md">
                        <div class="w-full max-w-sm mx-auto h-64 relative">
                            <canvas id="donationPieChart"></canvas>
                        </div>
                        <div class="mt-4 space-y-2 text-center">
                            @foreach ($donationSegments as $donationSegment)
                                <p class="text-sm text-gray-600">
                                    <span class="inline-block h-3 w-3 rounded-full align-middle" style="background-color: {{ $donationSegment['color'] }}"></span>
                                    {{ $donationSegment['label'] }}: {{ $donationSegment['value'] }}%
                                </p>
                            @endforeach
                        </div>
                    </div>
                    @push('scripts')
                        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const canvas = document.getElementById('donationPieChart');
                                if (!canvas || !window.Chart) return;
                                new Chart(canvas.getContext('2d'), {
                                    type: 'pie',
                                    data: {
                                        labels: ['Direct Charitable Programmes', 'Fundraising'],
                                        datasets: [{
                                            label: 'Donation allocation',
                                            data: [75, 25],
                                            backgroundColor: ['#10b981', '#f59e0b']
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { position: 'bottom' }
                                        }
                                    }
                                });
                            });
                        </script>
                    @endpush
                </div>
            </section>
        @elseif (str_contains(strtolower((string) $group), 'even a little donation'))
        <section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="mb-6 flex items-center gap-3">
                    <span class="h-1 w-10 rounded-full bg-red-700"></span>
                    <h2 class="text-2xl font-bold text-gray-950">{{ $group }}</h2>
                </div>
                <div class="relative">
                    <div id="donationCarousel" class="flex overflow-x-auto snap-x snap-mandatory scroll-smooth gap-4 pb-4">
                        @foreach ($items as $card)
                            <div class="snap-center shrink-0">
                                @if ($card->image_url)
                                    <img src="{{ $card->image_url }}?v={{ $card->updated_at->timestamp }}" alt="{{ $card->alt_text ?: $card->title ?: $group }}" class="block h-75 w-auto max-w-full">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 px-3 py-2 shadow md:hidden" onclick="document.getElementById('donationCarousel').scrollBy({left:-300,behavior:'smooth'})">‹</button>
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 px-3 py-2 shadow md:hidden" onclick="document.getElementById('donationCarousel').scrollBy({left:300,behavior:'smooth'})">›</button>
                </div>
                <div class="mt-4 flex justify-center gap-2" id="donationDots">
                    @foreach ($items as $index => $card)
                        <button type="button" data-index="{{ $index }}" class="h-2.5 w-2.5 rounded-full bg-gray-300" aria-label="Slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        </section>
        @else
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

    @if (count($heroImages ?? []) > 1)
        @push('scripts')
            <script>
                (function () {
                    const slides = document.querySelectorAll('[data-hero-slide]');
                    if (slides.length < 2) return;
                    let current = 0;
                    setInterval(() => {
                        slides[current].classList.remove('opacity-45');
                        slides[current].classList.add('opacity-0');
                        current = (current + 1) % slides.length;
                        slides[current].classList.remove('opacity-0');
                        slides[current].classList.add('opacity-45');
                    }, 4000);
                })();
            </script>
        @endpush
    @endif
            @endif
        @endif
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

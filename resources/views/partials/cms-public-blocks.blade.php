@php
    $accentClasses = [
        'red' => 'text-red-700',
        'green' => 'text-green-700',
        'blue' => 'text-blue-700',
        'yellow' => 'text-yellow-700',
        'purple' => 'text-purple-700',
        'orange' => 'text-orange-700',
        'slate' => 'text-slate-700',
    ];

    $pageBlocks = collect()
        ->merge($cmsCards ?? collect())
        ->merge($cmsNews ?? collect())
        ->merge($cmsResources ?? collect())
        ->merge($cmsGallery ?? collect())
        ->merge($cmsCtas ?? collect())
        ->sortBy('sort_order')
        ->values();
@endphp

@if ($pageBlocks->isNotEmpty())
    <section class="bg-white px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex items-center gap-3">
                <span class="h-1 w-10 rounded-full bg-red-700"></span>
                <h2 class="text-xl font-bold text-green-800">Highlights</h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($pageBlocks as $block)
                    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        @if ($block->image_path)
                            <img src="{{ $block->image_url }}?v={{ $block->updated_at->timestamp }}" alt="{{ $block->title ?: 'NCC content image' }}" class="h-52 w-full object-cover">
                        @endif
                        <div class="p-5">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide {{ $accentClasses[$block->accent_color ?? 'green'] ?? 'text-green-700' }}">
                                {{ config('cms.types.' . $block->type, ucfirst($block->type)) }}
                            </p>
                            @if ($block->title)
                                <h3 class="text-lg font-bold text-slate-950">{{ $block->title }}</h3>
                            @endif
                            @if ($block->subtitle)
                                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $block->subtitle }}</p>
                            @endif
                            @if ($block->description)
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $block->description }}</p>
                            @endif
                            @if ($block->button_text && $block->button_url)
                                <a href="{{ $block->button_url }}" class="mt-4 inline-flex rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
                                    {{ $block->button_text }}
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

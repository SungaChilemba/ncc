@if (($stats ?? collect())->isNotEmpty())
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 text-center sm:grid-cols-2 md:grid-cols-4 md:gap-8">
            @foreach ($stats as $stat)
                <div class="flex flex-col items-center">
                    @if (! empty($stat->data['icon']))
                        <i class="fas {{ $stat->data['icon'] }} mb-2 text-2xl text-green-700" aria-hidden="true"></i>
                    @endif
                    <h3 class="text-2xl font-bold text-green-700 md:text-3xl">{{ $stat->title }}</h3>
                    @if ($stat->description)
                        <p class="mt-1 text-sm text-slate-700">{{ $stat->description }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
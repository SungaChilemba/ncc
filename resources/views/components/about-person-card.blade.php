@props(['person'])

@php
    $bioSections = collect($person->data['bio_sections'] ?? [])
        ->filter(fn ($section) => ! empty($section['heading']) || ! empty($section['body']));

    if ($bioSections->isEmpty() && $person->description) {
        $bioSections = collect([
            [
                'heading' => 'Professional Background',
                'body' => $person->description,
            ],
            [
                'heading' => 'Education & Certifications',
                'body' => 'This profile can be expanded in the CMS with education, certifications, and professional development details.',
            ],
        ]);
    }
@endphp

<article class="flex flex-col items-center rounded-lg bg-white p-4 text-center shadow" x-data="{ bioOpen: false }">
    @if ($person->image_url)
        <img src="{{ $person->image_url }}?v={{ $person->updated_at->timestamp }}" class="mb-4 h-40 w-32 rounded-md object-cover transition duration-300 hover:scale-105" alt="{{ $person->alt_text ?: $person->title }}">
    @endif
    <h3 class="text-lg font-bold">{{ $person->title }}</h3>
    <p class="text-sm text-gray-600">{{ $person->subtitle }}</p>
    @if ($bioSections->isNotEmpty())
        <button
            type="button"
            @click="bioOpen = true"
            class="mt-3 text-sm font-semibold text-green-700 hover:underline"
        >
            View Biography
        </button>
    @endif

    @if ($bioSections->isNotEmpty())
        <div
            x-show="bioOpen"
            x-cloak
            x-transition.opacity.duration.300ms
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm"
            @click.self="bioOpen = false"
            @keydown.escape.window="bioOpen = false"
        >
            <div
                x-show="bioOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-95"
                class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 text-left shadow-xl"
            >
                <button type="button" @click="bioOpen = false" class="absolute right-4 top-4 text-gray-400 transition hover:text-gray-600" aria-label="Close biography">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
                <div class="mb-6 text-center">
                    @if ($person->image_url)
                        <img src="{{ $person->image_url }}?v={{ $person->updated_at->timestamp }}" class="mx-auto mb-3 h-24 w-24 rounded-full object-cover" alt="{{ $person->alt_text ?: $person->title }}">
                    @endif
                    <h3 class="text-xl font-bold">{{ $person->title }}</h3>
                    <p class="text-gray-600">{{ $person->subtitle }}</p>
                </div>
                <div class="space-y-5 text-gray-700">
                    @foreach ($bioSections as $section)
                        <div>
                            @if (! empty($section['heading']))
                                <h4 class="mb-2 font-semibold text-green-700">{{ $section['heading'] }}</h4>
                            @endif
                            @if (! empty($section['body']))
                                <div class="space-y-3 text-sm leading-6">
                                    @foreach (preg_split("/\n\s*\n/", $section['body']) as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</article>

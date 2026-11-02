<section class="bg-white px-4 py-12 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8 text-center">
            <span class="mx-auto block h-1 w-10 rounded-full bg-red-700"></span>
            <h2 class="mt-4 text-2xl font-bold text-gray-950">{{ $title }}</h2>
            @if ($subtitle ?? null)
                <p class="mt-3 text-gray-600">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($faqs->isNotEmpty())
            <div class="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                @foreach ($faqs as $faq)
                    <details class="faq-item">
                        <summary class="flex cursor-pointer list-none items-center justify-center gap-3 px-5 py-4 text-center sm:px-6">
                            <h3 class="text-base font-semibold text-gray-950">{{ $faq->title }}</h3>
                            <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200 faq-chevron" aria-hidden="true"></i>
                        </summary>
                        <div class="px-5 pb-5 sm:px-6">
                            <div class="space-y-3 text-gray-600">
                                @foreach (preg_split("/\n\s*\n/", (string) ($faq->description ?? '')) as $paragraph)
                                    <p>{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        </div>
                    </details>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-gray-500 shadow-sm">
                No questions have been published yet.
            </div>
        @endif
    </div>
</section>

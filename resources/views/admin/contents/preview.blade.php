@extends('admin.layouts.app')

@section('title', 'Preview Content')
@section('heading', 'Preview Content')
@section('subheading', 'Review draft or published CMS content before it appears in the public layout.')

@section('content')
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_22rem]">
        <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ config('cms.sections.' . $content->section . '.label') }} / {{ config('cms.types.' . $content->type, ucfirst($content->type)) }}</p>
                    <h2 class="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{{ $content->title ?: 'Untitled item' }}</h2>
                    @if ($content->subtitle)
                        <p class="mt-2 text-lg text-slate-600 dark:text-slate-400">{{ $content->subtitle }}</p>
                    @endif
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $content->is_published ? 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400' }}">
                    {{ $content->is_published ? 'Published' : 'Draft' }}
                </span>
            </div>

            @if ($content->image_url)
                <img src="{{ $content->image_url }}" alt="{{ $content->alt_text ?: $content->title }}" class="mt-6 max-h-[28rem] w-full rounded-lg object-cover">
            @endif

            @if ($content->description)
                <div class="mt-6 space-y-4 text-base leading-7 text-slate-700 dark:text-slate-300">
                    @foreach (preg_split("/\n\s*\n/", $content->description) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            @endif

            @if ($content->data)
                <div class="mt-6 rounded-md bg-slate-50 dark:bg-slate-800/50 p-4">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Structured Data</p>
                    <pre class="mt-2 overflow-x-auto text-xs leading-5 text-slate-600 dark:text-slate-400">{{ json_encode($content->data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                @if ($content->public_url)
                    <a href="{{ $content->public_url }}" target="_blank" class="rounded-md bg-green-700 dark:bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">Open Public URL</a>
                @endif
                @if ($content->type === 'resource' && $content->slug && $content->file_path)
                    <a href="{{ route('resources.download', $content->slug) }}" class="rounded-md border border-emerald-600 dark:border-emerald-500 px-4 py-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40">Download File</a>
                @endif
                <a href="{{ route('admin.contents.edit', ['section' => $section, 'id' => $content->id]) }}" class="rounded-md border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/60">Edit</a>
            </div>
        </section>

        <aside class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <div class="border-b border-slate-200 dark:border-slate-800 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950 dark:text-slate-100">Revisions</h2>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($revisions as $revision)
                    <div class="px-5 py-4">
                        <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $revision->created_at?->format('j M Y, H:i') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $revision->snapshot['title'] ?? 'Untitled item' }}</p>
                        <form method="POST" action="{{ route('admin.contents.revisions.restore', ['section' => $section, 'id' => $content->id, 'revision' => $revision->id]) }}" class="mt-3" onsubmit="return confirm('Restore this revision? Current content will be saved as a new revision first.');">
                            @csrf
                            <button type="submit" class="rounded-md border border-green-200 dark:border-green-900/60 px-3 py-1.5 text-xs font-semibold text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40">Restore</button>
                        </form>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-slate-500 dark:text-slate-400">No revisions have been saved yet.</p>
                @endforelse
            </div>

            <div class="border-y border-slate-200 dark:border-slate-800 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950 dark:text-slate-100">Audit Trail</h2>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($audits as $audit)
                    <div class="px-5 py-4">
                        <p class="text-sm font-semibold text-slate-950 dark:text-slate-100">{{ ucfirst(str_replace('_', ' ', $audit->action)) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $audit->created_at?->format('j M Y, H:i') }}</p>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-slate-500 dark:text-slate-400">No audit entries yet.</p>
                @endforelse
            </div>
        </aside>
    </div>
@endsection

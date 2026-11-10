@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('heading', 'Media Library')
@section('subheading', 'Upload and reuse public images and documents across CMS content.')

@section('content')
    @php
        $kind = request('kind');
        $missingAlt = request()->boolean('missing_alt');
        $totalOnPage = $assets->count();
        $imagesOnPage = $assets->getCollection()->filter->is_image->count();
        $documentsOnPage = $totalOnPage - $imagesOnPage;
        $missingAltOnPage = $assets->getCollection()
            ->filter(fn ($asset) => $asset->is_image && blank($asset->alt_text))
            ->count();
    @endphp

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Visible assets</p>
            <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-slate-100">{{ $totalOnPage }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Images</p>
            <p class="mt-2 text-2xl font-bold text-green-700 dark:text-green-400">{{ $imagesOnPage }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Documents</p>
            <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-slate-100">{{ $documentsOnPage }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Missing alt text</p>
            <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $missingAltOnPage }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[24rem_1fr]">
        <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <div class="border-b border-slate-200 dark:border-slate-800 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950 dark:text-slate-100">Upload Media</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Add images or documents with reusable metadata for CMS content.</p>
            </div>
            <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-5 p-5">
                @csrf
                <div>
                    <label for="asset" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">File</label>
                    <input id="asset" type="file" name="asset" accept="image/png,image/jpeg,image/webp,application/pdf,.doc,.docx" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 dark:file:bg-slate-800 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 dark:file:text-slate-200">
                    @error('asset')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="alt_text" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Alt Text</label>
                    <input id="alt_text" type="text" name="alt_text" value="{{ old('alt_text') }}" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Required in practice for meaningful public images.</p>
                    @error('alt_text')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="caption" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Caption</label>
                    <input id="caption" type="text" name="caption" value="{{ old('caption') }}" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                    @error('caption')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">Description / Notes</label>
                    <textarea id="description" name="description" rows="4" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm leading-6 focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full rounded-md bg-green-700 dark:bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">Upload</button>
            </form>
        </section>

        <section class="space-y-4">
            <form method="GET" action="{{ route('admin.media.index') }}" class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_12rem_auto_auto]">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search media" class="flex-1 rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                    <select name="kind" class="rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                        <option value="">All media</option>
                        <option value="images" @selected($kind === 'images')>Images</option>
                        <option value="documents" @selected($kind === 'documents')>Documents</option>
                    </select>
                    <label class="flex items-center gap-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="missing_alt" value="1" @checked($missingAlt) class="rounded border-slate-300 dark:border-slate-700 text-green-700 dark:text-green-400 focus:ring-green-600">
                        Missing alt
                    </label>
                    <button type="submit" class="rounded-md bg-slate-900 dark:bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:hover:bg-slate-600">Search</button>
                </div>
            </form>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @forelse ($assets as $asset)
                    <article class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 shadow-sm">
                        @if ($asset->is_image)
                            <img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $asset->original_name }}" class="h-40 w-full rounded-md object-cover">
                        @else
                            <div class="flex h-40 flex-col items-center justify-center rounded-md bg-slate-100 dark:bg-slate-800 text-sm font-semibold text-slate-500 dark:text-slate-400">
                                <span class="text-3xl">DOC</span>
                                <span class="mt-2">{{ strtoupper(pathinfo($asset->path, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                            </div>
                        @endif
                        <div class="mt-3">
                            <p class="truncate text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $asset->original_name ?: basename($asset->path) }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $asset->path }}</p>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-1">{{ $asset->mime_type ?: 'Unknown type' }}</span>
                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-1">{{ $asset->human_size }}</span>
                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-1">{{ $asset->created_at?->format('j M Y') }}</span>
                            </div>
                            @if ($asset->alt_text)
                                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400"><span class="font-semibold">Alt:</span> {{ $asset->alt_text }}</p>
                            @elseif ($asset->is_image)
                                <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-400">Missing alt text</p>
                            @endif
                            @if ($asset->caption)
                                <p class="mt-2 text-xs text-slate-600 dark:text-slate-400"><span class="font-semibold">Caption:</span> {{ $asset->caption }}</p>
                            @endif
                            @if ($asset->description)
                                <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ str($asset->description)->limit(120) }}</p>
                            @endif
                            <div class="mt-3">
                                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Public URL</label>
                                <input type="text" readonly value="{{ $asset->url }}" class="mt-1 w-full rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 px-2 py-1.5 text-xs text-slate-600 dark:text-slate-400">
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ $asset->url }}" target="_blank" class="inline-flex rounded-md border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:border-green-600 dark:hover:border-green-500 hover:text-green-700 dark:hover:text-green-300">Open</a>
                                <details class="min-w-full">
                                    <summary class="w-fit cursor-pointer rounded-md border border-green-200 dark:border-green-900/60 px-3 py-1.5 text-xs font-semibold text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40">Edit Details</summary>
                                    <form method="POST" action="{{ route('admin.media.update', $asset) }}" enctype="multipart/form-data" class="mt-3 space-y-3 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 p-3">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="q" value="{{ request('q') }}">
                                        <input type="hidden" name="kind" value="{{ request('kind') }}">
                                        @if ($missingAlt)
                                            <input type="hidden" name="missing_alt" value="1">
                                        @endif
                                        <div>
                                            <label for="alt_text_{{ $asset->id }}" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Alt Text</label>
                                            <input id="alt_text_{{ $asset->id }}" type="text" name="alt_text" value="{{ old('alt_text', $asset->alt_text) }}" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-2 py-1.5 text-xs focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                                        </div>
                                        <div>
                                            <label for="caption_{{ $asset->id }}" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Caption</label>
                                            <input id="caption_{{ $asset->id }}" type="text" name="caption" value="{{ old('caption', $asset->caption) }}" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-2 py-1.5 text-xs focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                                        </div>
                                        <div>
                                            <label for="description_{{ $asset->id }}" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Description / Notes</label>
                                            <textarea id="description_{{ $asset->id }}" name="description" rows="3" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-2 py-1.5 text-xs leading-5 focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">{{ old('description', $asset->description) }}</textarea>
                                        </div>
                                        <div>
                                            <label for="asset_{{ $asset->id }}" class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Replace File</label>
                                            <input id="asset_{{ $asset->id }}" type="file" name="asset" accept="image/png,image/jpeg,image/webp,application/pdf,.doc,.docx" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-2 py-1.5 text-xs file:mr-2 file:rounded-md file:border-0 file:bg-white dark:file:bg-slate-800 file:px-2 file:py-1 file:text-xs file:font-semibold file:text-slate-700 dark:file:text-slate-200">
                                        </div>
                                        <button type="submit" class="rounded-md bg-green-700 dark:bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">Save Media</button>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('admin.media.destroy', $asset) }}" onsubmit="return confirm('Delete this media asset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-red-200 dark:border-red-900/60 px-3 py-1.5 text-xs font-semibold text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-8 text-center text-sm text-slate-500 dark:text-slate-400">No media assets found.</div>
                @endforelse
            </div>

            @if ($assets->hasPages())
                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 shadow-sm">
                    {{ $assets->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection

@extends('admin.layouts.app')

@php
    $editingItem = $editing ?? null;
    $selectedType = old('type', $editingItem->type ?? request('type', array_key_first($typeOptions)));
    $editingPublishable = $selectedType !== 'submission';
    $publishMode = old('publish_mode', ! $editingItem ? 'draft' : (! $editingItem->is_published ? 'draft' : ($editingItem->published_at && $editingItem->published_at->isFuture() ? 'scheduled' : 'published')));
    $publishAt = old('publish_at', optional($editingItem?->published_at)->format('Y-m-d\TH:i'));
    $structuredData = old('data_json') ? json_decode(old('data_json'), true) : ($editingItem->data ?? []);
    $structuredItems = old('item_label')
        ? collect(old('item_label'))->map(fn ($label, $index) => ['label' => $label, 'value' => old('item_value.' . $index)])->all()
        : ($structuredData['items'] ?? [['label' => '', 'value' => ''], ['label' => '', 'value' => '']]);
    $bioSections = old('bio_heading')
        ? collect(old('bio_heading'))->map(fn ($heading, $index) => ['heading' => $heading, 'body' => old('bio_body.' . $index)])->all()
        : ($structuredData['bio_sections'] ?? [['heading' => 'Professional Background', 'body' => ''], ['heading' => 'Education & Certifications', 'body' => '']]);
    $source = $structuredData['source'] ?? [];
@endphp

@section('title', ($editingItem ? 'Edit ' : 'Create ') . $sectionConfig['label'])
@section('heading', $editingItem ? 'Edit Content' : 'Add New Content')
@section('subheading', $sectionConfig['label'] . ' editor workspace')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.contents.index', ['section' => $section]) }}" class="font-semibold text-green-700 hover:underline">Back to {{ $sectionConfig['label'] }}</a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-500">{{ $editingItem ? 'Editing item #' . $editingItem->id : 'New item' }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($editingItem)
                <a href="{{ route('admin.contents.preview', ['section' => $section, 'id' => $editingItem->id]) }}" class="inline-flex items-center justify-center rounded-md border border-green-600 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">Preview Draft</a>
            @endif
            <a href="{{ route($sectionConfig['route']) }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">View Page</a>
        </div>
    </div>

    <form
        id="content-editor-form"
        method="POST"
        action="{{ $editingItem ? route('admin.contents.update', ['section' => $section, 'id' => $editingItem->id]) : route('admin.contents.store', ['section' => $section]) }}"
        enctype="multipart/form-data"
        class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]"
        data-autosave-key="cms-editor-{{ $section }}-{{ $editingItem->id ?? 'new' }}-{{ $selectedType }}"
    >
        @csrf
        @if ($editingItem)
            @method('PUT')
        @endif

        <main class="min-w-0 space-y-5">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-[16rem_1fr]">
                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700">Type</label>
                        <select id="type" name="type" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" data-type-select>
                            @foreach ($typeOptions as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected($selectedType === $typeKey)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="title" class="block text-sm font-semibold text-slate-700">Title</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $editingItem->title ?? '') }}" class="mt-1 w-full rounded-md border border-slate-300 px-4 py-3 text-2xl font-semibold text-slate-950 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        @error('title')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label for="slug" class="block text-sm font-semibold text-slate-700">Permalink</label>
                    <div class="mt-1 grid grid-cols-1 gap-2 md:grid-cols-[minmax(0,1fr)_15rem]">
                        <p class="min-h-10 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                            <span data-permalink-preview>{{ route($sectionConfig['route']) }}</span>
                        </p>
                        <input id="slug" type="text" name="slug" value="{{ old('slug', $editingItem->slug ?? '') }}" placeholder="optional-url-slug" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" data-slug-input>
                    </div>
                    @error('slug')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <label for="description" class="block text-sm font-semibold text-slate-700">Body / Description</label>
                    <input id="subtitle" type="text" name="subtitle" value="{{ old('subtitle', $editingItem->subtitle ?? '') }}" placeholder="Optional subtitle" class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    @error('subtitle')
                        <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3">
                    <button type="button" class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold hover:bg-slate-100" data-wrap-description="**|**">B</button>
                    <button type="button" class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs italic hover:bg-slate-100" data-wrap-description="*|*">I</button>
                    <button type="button" class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold hover:bg-slate-100" data-prefix-description="- ">List</button>
                </div>
                <textarea id="description" name="description" rows="22" class="min-h-[32rem] w-full resize-y border-0 px-5 py-4 text-base leading-7 text-slate-800 focus:outline-none focus:ring-0">{{ old('description', $editingItem->description ?? '') }}</textarea>
                @error('description')
                    <p class="px-5 pb-4 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-950">Structured Fields</h2>
                    <p class="mt-1 text-sm text-slate-500">Common content structures can be edited here instead of raw JSON.</p>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2" data-type-panel="stat">
                        <p class="text-xs font-semibold uppercase text-slate-500">Statistic</p>
                        <p class="text-sm text-slate-600">Use Title for the number, and Body / Description for the supporting label.</p>
                    </div>

                    <div class="space-y-3" data-type-panel="news resource">
                        <p class="text-xs font-semibold uppercase text-slate-500">Source Citation</p>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input type="text" name="source_name" value="{{ old('source_name', $source['name'] ?? '') }}" placeholder="Source name" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <input type="url" name="source_url" value="{{ old('source_url', $source['url'] ?? '') }}" placeholder="https://source.example/article" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <input type="date" name="source_posted_on" value="{{ old('source_posted_on', $source['posted_on'] ?? '') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <input type="text" name="source_excerpt" value="{{ old('source_excerpt', $source['visible_excerpt'] ?? '') }}" placeholder="Short visible excerpt" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="space-y-3" data-type-panel="card cta hero">
                        <p class="text-xs font-semibold uppercase text-slate-500">Repeating Details</p>
                        @foreach ($structuredItems as $item)
                            <div class="grid gap-3 md:grid-cols-2">
                                <input type="text" name="item_label[]" value="{{ $item['label'] ?? '' }}" placeholder="Label" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <input type="text" name="item_value[]" value="{{ $item['value'] ?? '' }}" placeholder="Value" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-3" data-type-panel="card">
                        <p class="text-xs font-semibold uppercase text-slate-500">Biography Sections</p>
                        @foreach ($bioSections as $sectionData)
                            <div class="rounded-md border border-slate-200 bg-slate-50 p-3">
                                <input type="text" name="bio_heading[]" value="{{ $sectionData['heading'] ?? '' }}" placeholder="Section heading" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <textarea name="bio_body[]" rows="5" placeholder="Section body" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm leading-6">{{ $sectionData['body'] ?? '' }}</textarea>
                            </div>
                        @endforeach
                        <p class="text-xs text-slate-500">Used by commissioner cards when the category is Commissioners.</p>
                    </div>

@if ($section === 'advertise')
                    <div class="space-y-2 rounded-md border border-slate-200 bg-slate-50 p-3">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="hero_featured" value="1" @checked(old('hero_featured', $editingItem?->data['is_featured'] ?? false)) class="rounded border-slate-300 text-green-600 focus:ring-green-600">
                            Feature this advert on the /advertise hero carousel
                        </label>
                        <p class="text-xs text-slate-500">Only flagged adverts appear in the hero. Expired and unpublished adverts are never shown.</p>
                    </div>
@endif

                    <details class="rounded-md border border-slate-200 bg-slate-50 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">Advanced JSON</summary>
                        <textarea id="data_json" name="data_json" rows="8" placeholder='{"items":[{"label":"Example","value":"100"}]}' class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-xs leading-5 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old('data_json', $editingItem?->data ? json_encode($editingItem->data, JSON_PRETTY_PRINT) : '') }}</textarea>
                    </details>
                    @error('data_json')
                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>
        </main>

        <aside class="space-y-5 xl:sticky xl:top-6 xl:self-start">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-950">Publish</h2>
                </div>
                <div class="space-y-4 p-4">
                    <p class="text-xs text-slate-500" data-autosave-status>Autosave is ready.</p>
                    <div data-publish-panel class="{{ $editingPublishable ? '' : 'hidden' }}">
                        <label for="publish_mode" class="block text-sm font-semibold text-slate-700">Status</label>
                        <select id="publish_mode" name="publish_mode" class="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="draft" @selected($publishMode === 'draft')>Draft</option>
                            <option value="published" @selected($publishMode === 'published')>Publish now</option>
                            <option value="scheduled" @selected($publishMode === 'scheduled')>Schedule</option>
                        </select>
                        <label for="publish_at" class="mt-3 block text-xs font-semibold text-slate-600">Scheduled Publish Time</label>
                        <input id="publish_at" type="datetime-local" name="publish_at" value="{{ $publishAt }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div data-protected-panel class="{{ $editingPublishable ? 'hidden' : '' }} rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                        <p class="text-sm font-semibold text-amber-800">Protected Submission</p>
                        <p class="mt-1 text-xs text-amber-700">Report submissions are private records and cannot be published.</p>
                    </div>
                    <div class="grid gap-2">
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
                            {{ $editingItem ? 'Save Changes' : 'Create Item' }}
                        </button>
                        <a href="{{ route('admin.contents.index', ['section' => $section]) }}" class="inline-flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-950">Metadata</h2>
                <div class="mt-3 space-y-3">
                    @if ($section === 'resources')
                        <div>
                            <label for="category" class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
                            <select id="category" name="category" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                <option value="">Select category</option>
                                @foreach (config('cms.resource_categories') as $catValue => $catLabel)
                                    <option value="{{ $catValue }}" @selected(old('category', $editingItem->category ?? '') === $catValue)>{{ $catLabel }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-slate-500">Matches frontend filters: {{ implode(', ', array_values(config('cms.resource_categories'))) }}.</p>
                        </div>
                    @elseif ($section === 'advertise')
                        <div>
                            <label for="category" class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
                            <select id="category" name="category" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                <option value="">Select category</option>
                                @foreach (config('cms.advertise_categories') as $catValue => $catLabel)
                                    <option value="{{ $catValue }}" @selected(old('category', $editingItem->category ?? '') === $catValue)>{{ $catLabel }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-slate-500">Ad card groups: {{ implode(', ', array_values(config('cms.advertise_categories'))) }}. Maps to Why Partner / Sponsorship / Sponsored Ads sections.</p>
                        </div>
                    @else
                        <input id="category" type="text" name="category" value="{{ old('category', $editingItem->category ?? '') }}" placeholder="Category" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @endif
                    <input id="location" type="text" name="location" value="{{ old('location', $editingItem->location ?? '') }}" placeholder="Location" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <input id="published_on" type="date" name="published_on" value="{{ old('published_on', optional($editingItem?->published_on)->format('Y-m-d')) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <select id="accent_color" name="accent_color" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                        @foreach (config('cms.accent_colors') as $colorKey => $colorLabel)
                            <option value="{{ $colorKey }}" @selected(old('accent_color', $editingItem->accent_color ?? 'red') === $colorKey)>{{ $colorLabel }}</option>
                        @endforeach
                    </select>
                    <input id="sort_order" type="number" name="sort_order" min="0" value="{{ old('sort_order', $editingItem->sort_order ?? 0) }}" placeholder="Sort order" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-950">Media</h2>
                <div class="mt-3 space-y-3">
                    <input id="image" type="file" name="image" accept="image/png,image/jpeg,image/webp" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700">
                    @if ($editingItem?->image_path)
                        <div class="flex items-center gap-3">
                            <img src="{{ $editingItem->image_url }}" alt="" class="h-16 w-20 rounded-md object-cover">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-600">
                                <input type="checkbox" name="_remove_image" value="1" class="rounded border-slate-300 text-green-700 focus:ring-green-600">
                                Remove image
                            </label>
                        </div>
                    @endif
                    <input id="alt_text" type="text" name="alt_text" value="{{ old('alt_text', $editingItem->alt_text ?? '') }}" placeholder="Image alt text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @if ($mediaAssets->isNotEmpty())
                        <select id="media_asset_id" name="media_asset_id" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">No library asset selected</option>
                            @foreach ($mediaAssets as $asset)
                                <option value="{{ $asset->id }}" @selected((string) old('media_asset_id', $editingItem->media_asset_id ?? '') === (string) $asset->id)>
                                    {{ $asset->original_name ?: basename($asset->path) }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('image')
                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-950">Document & Link</h2>
                <div class="mt-3 space-y-3">
                    <input id="document" type="file" name="document" accept="application/pdf,.doc,.docx" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700">
                    @if ($editingItem?->file_path)
                        <div class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2 text-sm">
                            <a href="{{ $editingItem->file_url }}" target="_blank" class="font-semibold text-green-700 hover:underline">Current document</a>
                            <label class="flex items-center gap-2 font-medium text-slate-600">
                                <input type="checkbox" name="_remove_document" value="1" class="rounded border-slate-300 text-green-700 focus:ring-green-600">
                                Remove
                            </label>
                        </div>
                    @endif
                    <input id="button_text" type="text" name="button_text" value="{{ old('button_text', $editingItem->button_text ?? '') }}" placeholder="Button text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <input id="button_url" type="text" name="button_url" value="{{ old('button_url', $editingItem->button_url ?? '') }}" placeholder="/news or https://example.com" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    @error('document')
                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>
        </aside>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('content-editor-form');

            if (!form) {
                return;
            }

            const typeSelect = form.querySelector('[data-type-select]');
            const titleInput = form.querySelector('#title');
            const slugInput = form.querySelector('[data-slug-input]');
            const permalinkPreview = form.querySelector('[data-permalink-preview]');
            const status = form.querySelector('[data-autosave-status]');
            const baseUrl = @json(route($sectionConfig['route']));
            const autosaveKey = form.dataset.autosaveKey;

            const slugify = (value) => value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            const updatePermalink = () => {
                const slug = slugInput?.value || slugify(titleInput?.value || '');
                permalinkPreview.textContent = slug ? `${baseUrl.replace(/\/$/, '')}/${slug}` : baseUrl;
            };

            const updateTypePanels = () => {
                const type = typeSelect?.value || '';

                form.querySelectorAll('[data-type-panel]').forEach((panel) => {
                    const types = panel.dataset.typePanel.split(' ');
                    panel.classList.toggle('hidden', !types.includes(type));
                });

                form.querySelector('[data-publish-panel]')?.classList.toggle('hidden', type === 'submission');
                form.querySelector('[data-protected-panel]')?.classList.toggle('hidden', type !== 'submission');
            };

            const saveDraft = () => {
                const data = {};

                new FormData(form).forEach((value, key) => {
                    if (value instanceof File) {
                        return;
                    }

                    if (key.endsWith('[]')) {
                        data[key] ??= [];
                        data[key].push(value);
                    } else if (!key.startsWith('_')) {
                        data[key] = value;
                    }
                });

                localStorage.setItem(autosaveKey, JSON.stringify({ savedAt: new Date().toISOString(), data }));

                if (status) {
                    status.textContent = 'Autosaved locally at ' + new Date().toLocaleTimeString();
                }
            };

            const restoreDraft = () => {
                const saved = localStorage.getItem(autosaveKey);

                if (!saved || !confirm('Restore the locally autosaved draft for this editor?')) {
                    return;
                }

                const { data } = JSON.parse(saved);

                Object.entries(data).forEach(([key, value]) => {
                    const fields = form.querySelectorAll(`[name="${CSS.escape(key)}"]`);

                    fields.forEach((field, index) => {
                        field.value = Array.isArray(value) ? (value[index] || '') : value;
                    });
                });

                updateTypePanels();
                updatePermalink();
            };

            let timer;
            form.addEventListener('input', () => {
                updatePermalink();
                clearTimeout(timer);
                timer = setTimeout(saveDraft, 700);
            });
            form.addEventListener('change', () => {
                updateTypePanels();
                updatePermalink();
                saveDraft();
            });
            form.addEventListener('submit', () => localStorage.removeItem(autosaveKey));

            if (localStorage.getItem(autosaveKey) && status) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'ml-2 font-semibold text-green-700 hover:underline';
                button.textContent = 'Restore draft';
                button.addEventListener('click', restoreDraft);
                status.appendChild(button);
            }

            form.querySelectorAll('[data-wrap-description]').forEach((button) => {
                button.addEventListener('click', () => {
                    const textarea = form.querySelector('#description');
                    const [before, after] = button.dataset.wrapDescription.split('|');
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const selected = textarea.value.slice(start, end);
                    textarea.setRangeText(before + selected + after, start, end, 'select');
                    textarea.focus();
                });
            });

            form.querySelectorAll('[data-prefix-description]').forEach((button) => {
                button.addEventListener('click', () => {
                    const textarea = form.querySelector('#description');
                    textarea.setRangeText(button.dataset.prefixDescription, textarea.selectionStart, textarea.selectionStart, 'end');
                    textarea.focus();
                });
            });

            updateTypePanels();
            updatePermalink();
        });
    </script>
@endpush

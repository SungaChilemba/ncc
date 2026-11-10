@extends('admin.layouts.app')

@php
    $editingItem = $editing ?? null;
    $selectedType = $selectedType ?? old('type', $editingItem->type ?? request('type', array_key_first($typeOptions)));
    $selectedCategory = $selectedCategory ?? old('category', $editingItem->category ?? '');
    $initialSchema = $initialSchema ?? \App\Support\CmsEditorSchema::for($section, (string) $selectedType, (string) $selectedCategory);
    $initialBlocks = $initialSchema['blocks'] ?? [];
    $initialFields = $initialSchema['fields'] ?? [];
    $initialFieldMeta = $initialSchema['field_meta'] ?? [];
    $initialRequired = $initialSchema['required'] ?? [];
    $initialCategoryOptions = $initialSchema['category_options'] ?? null;
    $initialCategoryAllowCustom = $initialSchema['category_allow_custom'] ?? false;
    $initialCategoryMode = $initialCategoryOptions === null ? 'text' : ($initialCategoryAllowCustom ? 'datalist' : 'select');
    $fieldLabel = fn (string $key, string $default) => $initialFieldMeta[$key]['label'] ?? $default;
    $fieldHelp = fn (string $key) => $initialFieldMeta[$key]['help'] ?? null;
    $blockClass = fn (string $block) => in_array($block, $initialBlocks, true) ? '' : 'hidden';
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
    $newsPlacements = old('news_placement', $structuredData['placements'] ?? (($structuredData['is_featured'] ?? false) ? ['hero', 'featured'] : []));
    $structuredText = function (array $data, string $key) {
        $value = $data[$key] ?? null;
        return is_array($value) ? implode("\n", $value) : (string) ($value ?? '');
    };
    $bulletsText = old('bullets.0', $structuredText($structuredData, 'bullets'));
    $tierPlacementText = old('tier_placement.0', $structuredText($structuredData, 'placements'));
    $tierIncludeText = old('tier_include.0', $structuredText($structuredData, 'includes'));
    $endsAt = old('ends_at', $structuredData['ends_at'] ?? '');
    $statIcon = old('stat_icon', $structuredData['icon'] ?? '');
    $youtubeUrl = old('youtube_url', $structuredData['youtube_url'] ?? $structuredData['video_url'] ?? '');
    $allocation = $structuredData['allocation'] ?? [];
    $resourceSize = old('size', $structuredData['size'] ?? '');
    $resourcePages = old('pages', $structuredData['pages'] ?? '');
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

    <div class="mb-5 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900" data-context-banner>
        <i class="fa-solid fa-circle-info shrink-0 text-green-700"></i>
        <span>Showing fields used by <strong data-banner-type>{{ $initialSchema['type_label'] ?? '' }}</strong> on the <strong>{{ $sectionConfig['label'] }}</strong> page.</span>
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
            {{-- Panel 1: What this is --}}
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <header class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-700 text-sm font-bold text-white">1</span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">What this is</h2>
                        <p class="text-xs text-slate-500">Content type, title, category, and URL. Fields marked with <span class="font-semibold text-red-600">*</span> are required.</p>
                    </div>
                </header>
                <div class="space-y-4 p-5">
                    <div data-editor-block="identity" class="{{ $blockClass('identity') }}">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-[16rem_1fr]">
                            <div>
                                <label for="type" class="block text-sm font-semibold text-slate-700"><span data-field-label="type">{{ $fieldLabel('type', 'Content type') }}</span></label>
                                @if ($fieldHelp('type'))
                                    <p data-field-help="type" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('type') }}</p>
                                @endif
                                <select id="type" name="type" class="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" data-type-select>
                                    @foreach ($typeOptions as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}" @selected($selectedType === $typeKey)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="title" class="block text-sm font-semibold text-slate-700"><span data-field-label="title">{{ $fieldLabel('title', 'Title') }}</span> <span data-required-mark="title" class="font-semibold text-red-600">{{ in_array('title', $initialRequired, true) ? '*' : '' }}</span></label>
                                <input id="title" type="text" name="title" value="{{ old('title', $editingItem->title ?? '') }}" class="mt-2 w-full rounded-md border border-slate-300 px-4 py-3 text-2xl font-semibold text-slate-950 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                @error('title')
                                    <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div data-editor-block="category" class="{{ $blockClass('category') }}">
                        <label for="category" class="block text-sm font-semibold text-slate-700"><span data-field-label="category">{{ $fieldLabel('category', 'Category') }}</span> <span data-required-mark="category" class="font-semibold text-red-600">{{ in_array('category', $initialRequired, true) ? '*' : '' }}</span></label>
                        @if ($fieldHelp('category'))
                            <p data-field-help="category" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('category') }}</p>
                        @endif
                        <select id="category" name="category" data-category-select class="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100 {{ $initialCategoryMode === 'select' ? '' : 'hidden' }}" {{ $initialCategoryMode === 'select' ? '' : 'disabled' }}>
                            <option value="">Select category</option>
                            @foreach (array_keys($initialCategoryOptions ?? []) as $categoryValue)
                                <option value="{{ $categoryValue }}" @selected(old('category', $editingItem->category ?? '') === $categoryValue)>{{ $initialCategoryOptions[$categoryValue] }}</option>
                            @endforeach
                        </select>
                        <input id="category-datalist" type="text" name="category" list="category-list" value="{{ old('category', $editingItem->category ?? '') }}" placeholder="Category" data-category-input class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100 {{ $initialCategoryMode === 'datalist' ? '' : 'hidden' }}" {{ $initialCategoryMode === 'datalist' ? '' : 'disabled' }}>
                        <input id="category-text" type="text" name="category" value="{{ old('category', $editingItem->category ?? '') }}" placeholder="Category" data-category-text class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100 {{ $initialCategoryMode === 'text' ? '' : 'hidden' }}" {{ $initialCategoryMode === 'text' ? '' : 'disabled' }}>
                        <datalist id="category-list" data-category-list>
                            @foreach (array_keys($initialCategoryOptions ?? []) as $categoryValue)
                                <option value="{{ $categoryValue }}"></option>
                            @endforeach
                        </datalist>
                        @error('category')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div data-editor-block="permalink" class="{{ $blockClass('permalink') }}">
                        <label for="slug" class="block text-sm font-semibold text-slate-700"><span data-field-label="slug">{{ $fieldLabel('slug', 'URL slug') }}</span></label>
                        @if ($fieldHelp('slug'))
                            <p data-field-help="slug" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('slug') }}</p>
                        @endif
                        <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-[minmax(0,1fr)_15rem]">
                            <p class="min-h-10 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                <span data-permalink-preview>{{ route($sectionConfig['route']) }}</span>
                            </p>
                            <input id="slug" type="text" name="slug" value="{{ old('slug', $editingItem->slug ?? '') }}" placeholder="optional-url-slug" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100" data-slug-input>
                        </div>
                        @error('slug')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- Panel 2: What visitors see --}}
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <header class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-700 text-sm font-bold text-white">2</span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">What visitors see</h2>
                        <p class="text-xs text-slate-500">Only the fields this page actually uses.</p>
                    </div>
                </header>
                <div class="space-y-5 p-5">
                    <div data-editor-block="subtitle" class="{{ $blockClass('subtitle') }}">
                        <label for="subtitle" class="block text-sm font-semibold text-slate-700"><span data-field-label="subtitle">{{ $fieldLabel('subtitle', 'Subtitle') }}</span></label>
                        @if ($fieldHelp('subtitle'))
                            <p data-field-help="subtitle" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('subtitle') }}</p>
                        @endif
                        <input id="subtitle" type="text" name="subtitle" value="{{ old('subtitle', $editingItem->subtitle ?? '') }}" placeholder="Optional subtitle" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        @error('subtitle')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div data-editor-block="body" class="{{ $blockClass('body') }}">
                        <label for="description" class="block text-sm font-semibold text-slate-700"><span data-field-label="description">{{ $fieldLabel('description', 'Body / description') }}</span></label>
                        @if ($fieldHelp('description'))
                            <p data-field-help="description" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('description') }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-2 rounded-t-md border border-b-0 border-slate-300 bg-slate-50 px-3 py-2">
                            <button type="button" class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold hover:bg-slate-100" data-wrap-description="**|**">B</button>
                            <button type="button" class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs italic hover:bg-slate-100" data-wrap-description="*|*">I</button>
                            <button type="button" class="rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold hover:bg-slate-100" data-prefix-description="- ">List</button>
                        </div>
                        <textarea id="description" name="description" rows="22" class="min-h-[32rem] w-full resize-y rounded-b-md border border-slate-300 px-5 py-4 text-base leading-7 text-slate-800 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old('description', $editingItem->description ?? '') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        @if ($selectedType === 'stat')
                            <p class="mt-2 text-xs text-slate-500">Use Title for the number, and Body / Description for the supporting label.</p>
                        @endif
                    </div>

                    <div class="rounded-md border border-slate-200 bg-slate-50/70 p-4">
                        <h3 class="text-base font-semibold text-slate-950">Structured Fields</h3>
                        <p class="mt-1 text-sm text-slate-500">Common content structures can be edited here instead of raw JSON.</p>

                        <div class="mt-4 space-y-4">
                            <div data-editor-block="source" class="{{ $blockClass('source') }} space-y-2">
                                <p class="text-xs font-semibold uppercase text-slate-500">Source citation</p>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <input type="text" name="source_name" value="{{ old('source_name', $source['name'] ?? '') }}" placeholder="Source name" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    <input type="url" name="source_url" value="{{ old('source_url', $source['url'] ?? '') }}" placeholder="https://source.example/article" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    <input type="date" name="source_posted_on" value="{{ old('source_posted_on', $source['posted_on'] ?? '') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    <input type="text" name="source_excerpt" value="{{ old('source_excerpt', $source['visible_excerpt'] ?? '') }}" placeholder="Short visible excerpt" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                </div>
                            </div>

                            <div data-editor-block="items" class="{{ $blockClass('items') }} space-y-3">
                                <p class="text-xs font-semibold uppercase text-slate-500">Repeating details</p>
                                <div data-repeater="items" class="space-y-3">
                                    @foreach ($structuredItems as $item)
                                        <div data-row class="grid gap-3 md:grid-cols-2">
                                            <input type="text" name="item_label[]" value="{{ $item['label'] ?? '' }}" placeholder="Label" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            <div class="flex gap-2">
                                                <input type="text" name="item_value[]" value="{{ $item['value'] ?? '' }}" placeholder="Value" class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                                <button type="button" data-remove-row class="shrink-0 rounded-md border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:border-red-400 hover:text-red-600">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" data-add-row="items" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    Add row
                                </button>
                            </div>

                            <div data-editor-block="bio" class="{{ $blockClass('bio') }} space-y-3">
                                <p class="text-xs font-semibold uppercase text-slate-500">Biography sections</p>
                                <div data-repeater="bio" class="space-y-3">
                                    @foreach ($bioSections as $sectionData)
                                        <div data-row class="rounded-md border border-slate-200 bg-white p-3">
                                            <div class="flex items-start gap-2">
                                                <div class="min-w-0 flex-1">
                                                    <input type="text" name="bio_heading[]" value="{{ $sectionData['heading'] ?? '' }}" placeholder="Section heading" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                                    <textarea name="bio_body[]" rows="5" placeholder="Section body" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm leading-6 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ $sectionData['body'] ?? '' }}</textarea>
                                                </div>
                                                <button type="button" data-remove-row class="mt-1 shrink-0 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:border-red-400 hover:text-red-600">Remove</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" data-add-row="bio" class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    Add section
                                </button>
                                <p class="text-xs text-slate-500">Used by Commissioners, Ex-Officials, and Management cards to power the View Biography window.</p>
                            </div>

                            <div data-editor-block="bullets" class="{{ $blockClass('bullets') }} space-y-2">
                                <label for="bullets" class="block text-sm font-semibold text-slate-700"><span data-field-label="bullets">{{ $fieldLabel('bullets', 'Bullet points') }}</span></label>
                                @if ($fieldHelp('bullets'))
                                    <p data-field-help="bullets" class="text-xs text-slate-500">{{ $fieldHelp('bullets') }}</p>
                                @endif
                                <textarea id="bullets" name="bullets[]" rows="6" placeholder="One bullet per line" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm leading-6 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ $bulletsText }}</textarea>
                            </div>

                            <div data-editor-block="tier" class="{{ $blockClass('tier') }} space-y-3">
                                <p class="text-xs font-semibold uppercase text-slate-500">Sponsorship tier</p>
                                <div class="grid gap-3 md:grid-cols-3">
                                    <div>
                                        <label for="price" class="block text-xs font-semibold text-slate-600" data-field-label="price">{{ $fieldLabel('price', 'Package price') }}</label>
                                        <input id="price" type="text" name="price" value="{{ old('price', $structuredData['price'] ?? '') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    </div>
                                    <div>
                                        <label for="badge" class="block text-xs font-semibold text-slate-600" data-field-label="badge">{{ $fieldLabel('badge', 'Package badge') }}</label>
                                        <input id="badge" type="text" name="badge" value="{{ old('badge', $structuredData['badge'] ?? '') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    </div>
                                    <div>
                                        <label for="reach" class="block text-xs font-semibold text-slate-600" data-field-label="reach">{{ $fieldLabel('reach', 'Package reach') }}</label>
                                        <input id="reach" type="text" name="reach" value="{{ old('reach', $structuredData['reach'] ?? '') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    </div>
                                </div>
                                <div>
                                    <label for="tier_placement" class="block text-xs font-semibold text-slate-600" data-field-label="tier_placement">{{ $fieldLabel('tier_placement', 'Ad placements') }}</label>
                                    @if ($fieldHelp('tier_placement'))
                                        <p data-field-help="tier_placement" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('tier_placement') }}</p>
                                    @endif
                                    <textarea id="tier_placement" name="tier_placement[]" rows="3" placeholder="One placement per line" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm leading-6 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ $tierPlacementText }}</textarea>
                                </div>
                                <div>
                                    <label for="tier_include" class="block text-xs font-semibold text-slate-600" data-field-label="tier_include">{{ $fieldLabel('tier_include', 'What is included') }}</label>
                                    @if ($fieldHelp('tier_include'))
                                        <p data-field-help="tier_include" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('tier_include') }}</p>
                                    @endif
                                    <textarea id="tier_include" name="tier_include[]" rows="3" placeholder="One included item per line" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm leading-6 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ $tierIncludeText }}</textarea>
                                </div>
                            </div>

                            <div data-editor-block="stat_icon" class="{{ $blockClass('stat_icon') }} space-y-2">
                                <label for="stat_icon" class="block text-sm font-semibold text-slate-700"><span data-field-label="stat_icon">{{ $fieldLabel('stat_icon', 'Stat icon') }}</span></label>
                                @if ($fieldHelp('stat_icon'))
                                    <p data-field-help="stat_icon" class="text-xs text-slate-500">{{ $fieldHelp('stat_icon') }}</p>
                                @endif
                                <input id="stat_icon" type="text" name="stat_icon" list="icon-list" value="{{ $statIcon }}" placeholder="fa-solid fa-handshake" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                <datalist id="icon-list">
                                    @foreach (config('cms.icon_classes', []) as $iconClass)
                                        <option value="{{ $iconClass }}"></option>
                                    @endforeach
                                </datalist>
                            </div>

                            <div data-editor-block="submission_meta" class="{{ $blockClass('submission_meta') }} space-y-3">
                                <p class="text-xs font-semibold uppercase text-slate-500">Enquiry</p>
                                <div class="grid gap-3 md:grid-cols-3">
                                    <div>
                                        <label for="enquiry_status" class="block text-xs font-semibold text-slate-600" data-field-label="enquiry_status">{{ $fieldLabel('enquiry_status', 'Enquiry status') }}</label>
                                        <select id="enquiry_status" name="enquiry_status" class="mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                            @foreach (['pending_review' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $statusKey => $statusLabel)
                                                <option value="{{ $statusKey }}" @selected(old('enquiry_status', $structuredData['status'] ?? 'pending_review') === $statusKey)>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="company" class="block text-xs font-semibold text-slate-600" data-field-label="company">{{ $fieldLabel('company', 'Company / partner') }}</label>
                                        <input id="company" type="text" name="company" value="{{ old('company', $structuredData['company'] ?? '') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    </div>
                                    <div>
                                        <label for="ends_at" class="block text-xs font-semibold text-slate-600" data-field-label="ends_at">{{ $fieldLabel('ends_at', 'Campaign ends') }}</label>
                                        @if ($fieldHelp('ends_at'))
                                            <p data-field-help="ends_at" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('ends_at') }}</p>
                                        @endif
                                        <input id="ends_at" type="date" name="ends_at" value="{{ $endsAt }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                    </div>
                                </div>
                            </div>

                            <div data-editor-block="resource_meta" class="{{ $blockClass('resource_meta') }} grid gap-3 md:grid-cols-2">
                                <div>
                                    <label for="size" class="block text-sm font-semibold text-slate-700"><span data-field-label="size">{{ $fieldLabel('size', 'File size') }}</span></label>
                                    <input id="size" type="text" name="size" value="{{ $resourceSize }}" placeholder="e.g. 2.4 MB" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                </div>
                                <div>
                                    <label for="pages" class="block text-sm font-semibold text-slate-700"><span data-field-label="pages">{{ $fieldLabel('pages', 'Page count') }}</span></label>
                                    <input id="pages" type="number" name="pages" min="1" value="{{ $resourcePages }}" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                </div>
                            </div>

                            <div data-editor-block="video" class="{{ $blockClass('video') }} space-y-2">
                                <label for="youtube_url" class="block text-sm font-semibold text-slate-700"><span data-field-label="youtube_url">{{ $fieldLabel('youtube_url', 'Video URL') }}</span></label>
                                @if ($fieldHelp('youtube_url'))
                                    <p data-field-help="youtube_url" class="text-xs text-slate-500">{{ $fieldHelp('youtube_url') }}</p>
                                @endif
                                <input id="youtube_url" type="url" name="youtube_url" value="{{ $youtubeUrl }}" placeholder="https://www.youtube.com/embed/..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                            </div>

                            <div data-editor-block="allocation" class="{{ $blockClass('allocation') }} grid gap-3 md:grid-cols-2">
                                <div>
                                    <label for="allocation_direct" class="block text-sm font-semibold text-slate-700"><span data-field-label="allocation_direct">{{ $fieldLabel('allocation_direct', 'Direct programme %') }}</span></label>
                                    <input id="allocation_direct" type="number" name="allocation_direct" min="0" max="100" value="{{ old('allocation_direct', $allocation['direct'] ?? '') }}" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                </div>
                                <div>
                                    <label for="allocation_fundraising" class="block text-sm font-semibold text-slate-700"><span data-field-label="allocation_fundraising">{{ $fieldLabel('allocation_fundraising', 'Fundraising %') }}</span></label>
                                    <input id="allocation_fundraising" type="number" name="allocation_fundraising" min="0" max="100" value="{{ old('allocation_fundraising', $allocation['fundraising'] ?? '') }}" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div data-editor-block="image" class="{{ $blockClass('image') }} space-y-2">
                        <label for="image" class="block text-sm font-semibold text-slate-700"><span data-field-label="image">{{ $fieldLabel('image', 'Main image') }}</span></label>
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
                        @error('image')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div data-editor-block="alt_text" class="{{ $blockClass('alt_text') }} space-y-2">
                        <label for="alt_text" class="block text-sm font-semibold text-slate-700"><span data-field-label="alt_text">{{ $fieldLabel('alt_text', 'Image description (alt text)') }}</span></label>
                        @if ($fieldHelp('alt_text'))
                            <p data-field-help="alt_text" class="text-xs text-slate-500">{{ $fieldHelp('alt_text') }}</p>
                        @endif
                        <input id="alt_text" type="text" name="alt_text" value="{{ old('alt_text', $editingItem->alt_text ?? '') }}" placeholder="Image alt text" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    </div>

                    <div data-editor-block="media_library" class="{{ $blockClass('media_library') }} space-y-2">
                        <label for="media_asset_id" class="block text-sm font-semibold text-slate-700"><span data-field-label="media_asset_id">{{ $fieldLabel('media_asset_id', 'Use a media library file') }}</span></label>
                        <select id="media_asset_id" name="media_asset_id" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                            <option value="">No library asset selected</option>
                            @foreach ($mediaAssets as $asset)
                                <option value="{{ $asset->id }}" @selected((string) old('media_asset_id', $editingItem->media_asset_id ?? '') === (string) $asset->id)>
                                    {{ $asset->original_name ?: basename($asset->path) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div data-editor-block="document" class="{{ $blockClass('document') }} space-y-2">
                        <label for="document" class="block text-sm font-semibold text-slate-700"><span data-field-label="document">{{ $fieldLabel('document', 'Document file') }}</span></label>
                        @if ($fieldHelp('document'))
                            <p data-field-help="document" class="text-xs text-slate-500">{{ $fieldHelp('document') }}</p>
                        @endif
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
                        @error('document')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div data-editor-block="button" class="{{ $blockClass('button') }} grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="button_text" class="block text-sm font-semibold text-slate-700"><span data-field-label="button_text">{{ $fieldLabel('button_text', 'Button label') }}</span></label>
                            <input id="button_text" type="text" name="button_text" value="{{ old('button_text', $editingItem->button_text ?? '') }}" placeholder="Button text" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        </div>
                        <div>
                            <label for="button_url" class="block text-sm font-semibold text-slate-700"><span data-field-label="button_url">{{ $fieldLabel('button_url', 'Button link') }}</span></label>
                            @if ($fieldHelp('button_url'))
                                <p data-field-help="button_url" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('button_url') }}</p>
                            @endif
                            <input id="button_url" type="text" name="button_url" value="{{ old('button_url', $editingItem->button_url ?? '') }}" placeholder="/news or https://example.com" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        </div>
                    </div>

                    <div data-editor-block="button_label" class="{{ $blockClass('button_label') }}">
                        <label for="button_text-only" class="block text-sm font-semibold text-slate-700"><span data-field-label="button_text">{{ $fieldLabel('button_text', 'Button label') }}</span></label>
                        @if ($fieldHelp('button_text'))
                            <p data-field-help="button_text" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('button_text') }}</p>
                        @endif
                        <input id="button_text-only" type="text" name="button_text" value="{{ old('button_text', $editingItem->button_text ?? '') }}" placeholder="Button text" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    </div>
                </div>
            </section>

            {{-- Panel 3: Where and when it appears --}}
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <header class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-700 text-sm font-bold text-white">3</span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Where and when it appears</h2>
                        <p class="text-xs text-slate-500">Display order, publishing, and visibility toggles.</p>
                    </div>
                </header>
                <div class="grid gap-4 p-5 md:grid-cols-2">
                    <div data-editor-block="location" class="{{ $blockClass('location') }}">
                        <label for="location" class="block text-sm font-semibold text-slate-700"><span data-field-label="location">{{ $fieldLabel('location', 'Location') }}</span></label>
                        @if ($fieldHelp('location'))
                            <p data-field-help="location" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('location') }}</p>
                        @endif
                        <input id="location" type="text" name="location" list="location-list" value="{{ old('location', $editingItem->location ?? '') }}" placeholder="Location" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                        <datalist id="location-list">
                            @foreach (config('cms.locations', []) as $locationOption)
                                <option value="{{ $locationOption }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div data-editor-block="published_on" class="{{ $blockClass('published_on') }}">
                        <label for="published_on" class="block text-sm font-semibold text-slate-700"><span data-field-label="published_on">{{ $fieldLabel('published_on', 'Published date') }}</span></label>
                        @if ($fieldHelp('published_on'))
                            <p data-field-help="published_on" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('published_on') }}</p>
                        @endif
                        <input id="published_on" type="date" name="published_on" value="{{ old('published_on', optional($editingItem?->published_on)->format('Y-m-d')) }}" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    </div>

                    <div data-editor-block="accent" class="{{ $blockClass('accent') }}">
                        <label for="accent_color" class="block text-sm font-semibold text-slate-700"><span data-field-label="accent_color">{{ $fieldLabel('accent_color', 'Accent colour') }}</span></label>
                        <select id="accent_color" name="accent_color" class="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                            @foreach (config('cms.accent_colors') as $colorKey => $colorLabel)
                                <option value="{{ $colorKey }}" @selected(old('accent_color', $editingItem->accent_color ?? 'red') === $colorKey)>{{ $colorLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div data-editor-block="sort" class="{{ $blockClass('sort') }}">
                        <label for="sort_order" class="block text-sm font-semibold text-slate-700"><span data-field-label="sort_order">{{ $fieldLabel('sort_order', 'Display order') }}</span></label>
                        @if ($fieldHelp('sort_order'))
                            <p data-field-help="sort_order" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('sort_order') }}</p>
                        @endif
                        <input id="sort_order" type="number" name="sort_order" min="0" value="{{ old('sort_order', $editingItem->sort_order ?? 0) }}" placeholder="Sort order" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    </div>

                    <div data-editor-block="placements" class="{{ $blockClass('placements') }} md:col-span-2 rounded-md border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-sm font-semibold text-slate-950"><span data-field-label="news_placement">{{ $fieldLabel('news_placement', 'News placement') }}</span></p>
                        @if ($fieldHelp('news_placement'))
                            <p data-field-help="news_placement" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('news_placement') }}</p>
                        @endif
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" name="news_placement[]" value="hero" @checked(in_array('hero', (array) $newsPlacements, true)) class="rounded border-slate-300 text-green-700 focus:ring-green-600">
                                Hero carousel
                            </label>
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" name="news_placement[]" value="featured" @checked(in_array('featured', (array) $newsPlacements, true)) class="rounded border-slate-300 text-green-700 focus:ring-green-600">
                                Featured slot
                            </label>
                        </div>
                    </div>

                    <div data-editor-block="ad_expiry" class="{{ $blockClass('ad_expiry') }} md:col-span-2 rounded-md border border-slate-200 bg-slate-50/70 p-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="ends_at_ad" class="block text-sm font-semibold text-slate-700"><span data-field-label="ends_at">{{ $fieldLabel('ends_at', 'Campaign ends') }}</span></label>
                                @if ($fieldHelp('ends_at'))
                                    <p data-field-help="ends_at" class="mt-1 text-xs text-slate-500">{{ $fieldHelp('ends_at') }}</p>
                                @endif
                                <input id="ends_at_ad" type="date" name="ends_at" value="{{ $endsAt }}" class="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                            </div>
                            <div>
                                <label class="mt-6 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input type="checkbox" name="hero_featured" value="1" @checked(old('hero_featured', $editingItem?->data['is_featured'] ?? false)) class="rounded border-slate-300 text-green-600 focus:ring-green-600">
                                    <span data-field-label="hero_featured">{{ $fieldLabel('hero_featured', 'Feature in the advertise hero') }}</span>
                                </label>
                                <p class="mt-1 text-xs text-slate-500">Only flagged adverts appear in the hero. Expired and unpublished adverts are never shown.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div data-editor-block="json" class="{{ $blockClass('json') }}">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Developer options</p>
                <details class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-700">Advanced JSON</summary>
                    <p class="mt-2 text-xs text-slate-500">Escape hatch for fields not covered above. Structured fields above always win over JSON values here.</p>
                    <textarea id="data_json" name="data_json" rows="8" placeholder='{"items":[{"label":"Example","value":"100"}]}' class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-xs leading-5 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old('data_json', $editingItem?->data ? json_encode($editingItem->data, JSON_PRETTY_PRINT) : '') }}</textarea>
                </details>
                @error('data_json')
                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
            </div>
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
                <h2 class="text-sm font-semibold text-slate-950">At a glance</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Page</dt>
                        <dd class="font-semibold text-slate-900">{{ $sectionConfig['label'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Type</dt>
                        <dd class="font-semibold text-slate-900">{{ $initialSchema['type_label'] ?? '' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Fields shown</dt>
                        <dd class="font-semibold text-slate-900">{{ count($initialFields ?? []) }} fields</dd>
                    </div>
                </dl>
                <p class="mt-3 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">Fields not used by this page are hidden automatically.</p>
            </section>
        </aside>
    </form>
@endsection

@push('scripts')
    <script>
        window.cmsEditorSchema = @json($editorSchema);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('content-editor-form');

            if (!form) {
                return;
            }

            const schema = window.cmsEditorSchema || {};
            const schemaTypes = schema.types || {};
            const typeSelect = form.querySelector('[data-type-select]');
            const titleInput = form.querySelector('#title');
            const slugInput = form.querySelector('[data-slug-input]');
            const permalinkPreview = form.querySelector('[data-permalink-preview]');
            const status = form.querySelector('[data-autosave-status]');
            const baseUrl = @json(route($sectionConfig['route']));
            const autosaveKey = form.dataset.autosaveKey;

            const categorySelect = form.querySelector('[data-category-select]');
            const categoryInput = form.querySelector('[data-category-input]');
            const categoryList = form.querySelector('[data-category-list]');
            const categoryText = form.querySelector('[data-category-text]');
            const categoryControls = [categorySelect, categoryInput, categoryText].filter(Boolean);

            const slugify = (value) => value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

            const updatePermalink = () => {
                const slug = slugInput?.value || slugify(titleInput?.value || '');
                permalinkPreview.textContent = slug ? `${baseUrl.replace(/\/$/, '')}/${slug}` : baseUrl;
            };

            const matchCategoryKey = (map, category) => {
                if (!map || !category) {
                    return null;
                }

                if (Object.prototype.hasOwnProperty.call(map, category)) {
                    return category;
                }

                const normalised = category.trim().toLowerCase();

                return Object.keys(map).find((key) => key.trim().toLowerCase() === normalised) || null;
            };

            const resolveBlocks = (typeSchema, category) => {
                let blocks = (typeSchema && typeSchema.blocks) || [];

                if (category && typeSchema && typeSchema.category_blocks) {
                    const key = matchCategoryKey(typeSchema.category_blocks, category);

                    if (key) {
                        blocks = typeSchema.category_blocks[key];
                    }
                }

                return blocks;
            };

            const currentCategoryValue = () => categoryControls.find((element) => !element.disabled)?.value ?? '';

            const applyCategoryControl = (type) => {
                if (categoryControls.length === 0) {
                    return;
                }

                const typeSchema = schemaTypes[type] || {};
                const options = typeSchema.category_options || null;
                const keys = options ? Object.keys(options) : [];
                const mode = options ? (typeSchema.category_allow_custom ? 'datalist' : 'select') : 'text';
                const current = currentCategoryValue();

                if (mode === 'select' && categorySelect) {
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Select category';
                    categorySelect.replaceChildren(placeholder, ...keys.map((key) => {
                        const option = document.createElement('option');
                        option.value = key;
                        option.textContent = options[key];
                        return option;
                    }));
                    categorySelect.value = keys.includes(current) ? current : '';
                }

                if (mode === 'datalist' && categoryList) {
                    categoryList.replaceChildren(...keys.map((key) => {
                        const option = document.createElement('option');
                        option.value = key;
                        return option;
                    }));
                }

                categoryControls.forEach((control) => {
                    control.disabled = true;
                    control.classList.add('hidden');
                });

                const active = mode === 'select' ? categorySelect : (mode === 'datalist' ? categoryInput : categoryText);

                if (active) {
                    active.value = current;
                    active.disabled = false;
                    active.classList.remove('hidden');
                }
            };

            const updateEditorBlocks = () => {
                const type = typeSelect?.value || '';
                const typeSchema = schemaTypes[type] || {};
                const category = currentCategoryValue();
                const blocks = resolveBlocks(typeSchema, category);

                form.querySelectorAll('[data-editor-block]').forEach((block) => {
                    const visible = blocks.includes(block.dataset.editorBlock);
                    block.classList.toggle('hidden', !visible);
                    block.querySelectorAll('input, select, textarea, button').forEach((field) => {
                        field.disabled = !visible;
                    });
                });

                applyCategoryControl(type);

                const meta = { ...(typeSchema.field_meta || {}) };

                if (category && typeSchema.category_labels) {
                    const key = matchCategoryKey(typeSchema.category_labels, category);

                    if (key) {
                        Object.entries(typeSchema.category_labels[key]).forEach(([field, label]) => {
                            if (meta[field]) {
                                meta[field] = { ...meta[field], label };
                            }
                        });
                    }
                }

                form.querySelectorAll('[data-field-label]').forEach((label) => {
                    const field = label.dataset.fieldLabel;

                    if (meta[field]?.label) {
                        label.textContent = meta[field].label;
                    }
                });

                form.querySelectorAll('[data-field-help]').forEach((help) => {
                    const field = help.dataset.fieldHelp;
                    help.textContent = meta[field]?.help ?? '';
                    help.classList.toggle('hidden', !meta[field]?.help);
                });

                const requiredFields = typeSchema.required || [];
                form.querySelectorAll('[data-required-mark]').forEach((mark) => {
                    const field = mark.dataset.requiredMark;
                    const required = requiredFields.includes(field);
                    mark.textContent = required ? '*' : '';
                    mark.classList.toggle('hidden', !required);
                });

                form.querySelectorAll('[data-banner-type]').forEach((bannerType) => {
                    bannerType.textContent = typeSchema.type_label || '';
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
                        const singleValue = Array.isArray(value) ? (value[index] ?? '') : value;

                        if (field.type === 'checkbox' || field.type === 'radio') {
                            field.checked = Array.isArray(value)
                                ? value.includes(field.value)
                                : (singleValue === field.value || singleValue === true || singleValue === '1');
                        } else {
                            field.value = singleValue;
                        }
                    });
                });

                updateEditorBlocks();
                updatePermalink();
            };

            let timer;
            form.addEventListener('input', () => {
                updatePermalink();
                clearTimeout(timer);
                timer = setTimeout(saveDraft, 700);
            });
            form.addEventListener('change', () => {
                updateEditorBlocks();
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

            form.querySelectorAll('[data-add-row]').forEach((button) => {
                button.addEventListener('click', () => {
                    const container = form.querySelector(`[data-repeater="${button.dataset.addRow}"]`);

                    if (!container) {
                        return;
                    }

                    const [firstRow] = container.querySelectorAll('[data-row]');

                    if (!firstRow) {
                        return;
                    }

                    const row = firstRow.cloneNode(true);
                    row.querySelectorAll('input, textarea').forEach((field) => {
                        field.value = '';
                    });
                    container.appendChild(row);
                });
            });

            form.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-row]');

                if (removeButton) {
                    removeButton.closest('[data-row]')?.remove();
                }
            });

            updateEditorBlocks();
            updatePermalink();
        });
    </script>
@endpush

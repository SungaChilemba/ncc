@extends("layouts.app")

@section("title", "Resources")

@section("content")
				@php
								$suppressCmsBlocks = true;
								$resourceHero = $cmsHero ?? null;
								$resources = ($cmsResources ?? collect())->values();
								$categories = $resources->pluck("category")->filter()->unique()->values();

								// Map to lightweight JSON for Alpine — preserves backend contract (slug, file_url, route download/detail)
								// categoryKey normalized to slug (e.g. "Legal Instruments" -> "legal-instruments") to match updated static filters
								$resourcesMapped = $resources->map(function ($r) {
								    $rawCat = $r->category ?: '';
								    return [
								        'slug' => $r->slug,
								        'title' => $r->title,
								        'category' => $rawCat,
								        'categoryKey' => $rawCat ? \Illuminate\Support\Str::slug($rawCat) : '',
								        'categoryLabel' => $rawCat ?: 'Document',
								        'description' => $r->description,
								        'file_url' => $r->file_url,
								        'detail_url' => $r->slug ? route('resources.show', $r->slug) : null,
								        'download_url' => $r->slug ? route('resources.download', $r->slug) : null,
								        'hasFile' => (bool) $r->file_path,
								        'file_path' => $r->file_path,
								        'size' => $r->data['size'] ?? null,
								        'pages' => $r->data['pages'] ?? null,
								        'updated' => $r->updated_at ? $r->updated_at->format('d M Y') : ($r->published_on?->format('d M Y') ?? ''),
								        'button_text' => $r->button_text,
								    ];
								})->values();
				@endphp

				<div x-data="{
				    search: '',
				    activeFilter: 'all',
				    filtersOpen: false,
				    page: 1,
				    perPage: 4,
				    modalOpen: false,
				    modalResource: null,
				    recentlyViewed: [],
				    resources: @js($resourcesMapped),
				    // Static filters exactly as in updated/resources-updated.blade.php:20-28 — admin categories must use these labels (slug matches)
				    filters: [
				        { key: 'all', label: 'All' },
				        { key: 'acts', label: 'Acts' },
				        { key: 'guidelines', label: 'Guidelines' },
				        { key: 'legal-instruments', label: 'Legal Instruments' },
				        { key: 'strategic-policies', label: 'Strategic Policies' },
				        { key: 'stakeholder-tools', label: 'Stakeholder Tools' },
				        { key: 'reports', label: 'Reports' }
				    ],
				    init() {
				        this.loadRecentlyViewed();
				        this.$watch('activeFilter', () => { this.page = 1; });
				        this.$watch('search', () => { this.page = 1; });
				    },
				    get filteredCards() {
				        return this.resources.filter(c => {
				            const matchesFilter = this.activeFilter === 'all' || c.categoryKey === this.activeFilter || (!c.categoryKey && false);
				            const term = this.search.trim().toLowerCase();
				            const matchesSearch = !term || c.title.toLowerCase().includes(term) || (c.description && c.description.toLowerCase().includes(term)) || (c.categoryLabel && c.categoryLabel.toLowerCase().includes(term));
				            return matchesFilter && matchesSearch;
				        });
				    },
				    get totalPages() {
				        return Math.max(1, Math.ceil(this.filteredCards.length / this.perPage));
				    },
				    get paginatedCards() {
				        const start = (this.page - 1) * this.perPage;
				        return this.filteredCards.slice(start, start + this.perPage);
				    },
				    prevPage() {
				        if (this.page > 1) this.page--;
				    },
				    nextPage() {
				        if (this.page < this.totalPages) this.page++;
				    },
				    openPreview(card) {
				        this.modalResource = card;
				        this.modalOpen = true;
				        if (card.slug) this.pushRecent(card.slug);
				    },
				    closeModal() {
				        this.modalOpen = false;
				        this.modalResource = null;
				    },
				    pushRecent(slug) {
				        let ids = this.recentlyViewed.filter(i => i !== slug);
				        ids.unshift(slug);
				        this.recentlyViewed = ids.slice(0, 10);
				        localStorage.setItem('ncc_recently_viewed', JSON.stringify(this.recentlyViewed));
				    },
				    loadRecentlyViewed() {
				        try {
				            const stored = localStorage.getItem('ncc_recently_viewed');
				            if (stored) this.recentlyViewed = JSON.parse(stored);
				        } catch (e) { this.recentlyViewed = []; }
				    },
				    countForFilter(key) {
    const term = this.search.trim().toLowerCase();
    return this.resources.filter(c => {
        const matchesCat = key === 'all' || c.categoryKey === key;
        const matchesSearch = !term || c.title.toLowerCase().includes(term) || (c.description && c.description.toLowerCase().includes(term)) || (c.categoryLabel && c.categoryLabel.toLowerCase().includes(term));
        return matchesCat && matchesSearch;
    }).length;
},
clearFilters() {
    this.search = '';
    this.activeFilter = 'all';
    this.page = 1;
},
get activeFilterLabel() {
    const f = this.filters.find(f => f.key === this.activeFilter);
    return f ? f.label : '';
},
				    get recentCards() {
				        return this.recentlyViewed.map(slug => this.resources.find(c => c.slug === slug)).filter(Boolean);
				    }
				}" x-init="init()" @keydown.escape.window="closeModal()" class="max-w-7xl mx-auto px-4 py-10">

								<!-- Page Header (CMS-driven) -->
								<div class="grid gap-8 lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-end mb-8">
									<div>
										<h1 class="text-3xl font-bold text-emerald-700">{{ $resourceHero?->title ?: "Resources" }}</h1>
										<p class="mt-2 text-gray-600">
											{{ $resourceHero?->description ?: "Browse Official Documents Here" }}
										</p>
									</div>
									<!-- Search (moved out of the filters card) -->
									<div class="relative w-full">
										<i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
										<input type="text" placeholder="Search resources..." x-model="search"
											class="w-full rounded-lg border border-slate-200 py-2.5 pl-11 pr-4 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
									</div>
								</div>

								<!-- Recently Viewed (updated UI) — tracks slug, localStorage -->
								<div x-show="recentCards.length > 0" x-transition class="mb-8" x-cloak>
												<h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Recently Viewed</h2>
												<div class="flex gap-4 overflow-x-auto pb-2">
																<template x-for="card in recentCards" :key="card.slug">
																				<button @click="openPreview(card)"
																								class="flex-shrink-0 w-56 text-left bg-white border border-slate-200 rounded-lg p-3 hover:border-emerald-500 hover:shadow-md transition">
																								<span class="text-xs font-medium text-emerald-600" x-text="card.categoryLabel"></span>
																								<p class="text-sm font-semibold text-gray-800 mt-1 line-clamp-2" x-text="card.title"></p>
																								<span class="text-xs text-gray-400 mt-2 block" x-text="card.updated"></span>
																				</button>
																</template>
												</div>
								</div>

<!-- Filters sidebar + Results (dashboard layout) -->
<div class="grid gap-8 lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start">
	<!-- Filters: mobile toggle + panel -->
	<aside class="lg:sticky lg:top-24 lg:self-start">
		<!-- Mobile filter toggle -->
		<button type="button" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen"
			class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm lg:hidden">
			<span class="inline-flex items-center gap-2">
				<i class="fa-solid fa-sliders text-emerald-600"></i> Filters
			</span>
			<span class="inline-flex items-center gap-2">
				<span x-show="activeFilter !== 'all'" x-cloak
					class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700"
					x-text="activeFilterLabel"></span>
				<i class="fa-solid fa-chevron-down text-xs transition-transform" :class="filtersOpen ? 'rotate-180' : ''"></i>
			</span>
		</button>

		<!-- Panel: collapsible on mobile, always visible from lg up -->
		<div class="mt-4 lg:mt-0" :class="filtersOpen ? '' : 'hidden lg:block'">
			<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
				<div class="mb-4 flex items-center justify-between">
					<h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Filters</h2>
					<button type="button" x-show="search.length > 0 || activeFilter !== 'all'" x-cloak @click="clearFilters()"
						class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Clear</button>
				</div>
				<!-- Filters stacked top-to-bottom -->
				<nav class="space-y-1">
					<template x-for="filter in filters" :key="filter.key">
						<button type="button" @click="activeFilter = filter.key; filtersOpen = false" :aria-pressed="activeFilter === filter.key"
							:class="activeFilter === filter.key ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-700'"
							class="flex w-full items-center justify-between gap-2 rounded-lg px-3.5 py-2.5 text-left text-sm font-medium transition">
							<span x-text="filter.label"></span>
							<span class="rounded-full px-2 py-0.5 text-xs font-semibold"
								:class="activeFilter === filter.key ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500'"
								x-text="countForFilter(filter.key)"></span>
						</button>
					</template>
				</nav>
			</div>
		</div>
	</aside>

	<!-- Results column -->
	<div>
								<!-- Empty when no CMS resources at all -->
								<div x-show="resources.length === 0" class="rounded-lg border border-slate-200 bg-white p-8 text-center text-gray-500 mb-6" x-cloak>
												No resources have been published yet.
								</div>

								<!-- Resource Cards (updated UI + CMS data) -->
								<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
												<template x-for="card in paginatedCards" :key="card.slug">
																<div :data-category="card.categoryKey" class="bg-white shadow rounded-lg p-6 flex flex-col">
																				<span class="text-xs font-medium text-emerald-600 uppercase tracking-wide" x-text="card.categoryLabel"></span>
																				<h3 class="text-lg font-bold text-gray-800 mt-1 mb-2" x-text="card.title"></h3>
																				<p class="text-gray-600 text-sm mb-4 line-clamp-3" x-text="card.description"></p>

																				<!-- File metadata (fallback when admin hasn't set size/pages) -->
																				<div class="text-xs text-gray-500 flex flex-wrap gap-x-3 gap-y-1 mb-4">
																								<span class="inline-flex items-center gap-1"><i class="fa-solid fa-file-pdf text-red-500"></i> PDF</span>
																								<span class="inline-flex items-center gap-1" x-show="card.size"><i class="fa-solid fa-weight-hanging"></i> <span x-text="card.size"></span></span>
																								<span class="inline-flex items-center gap-1" x-show="card.pages"><i class="fa-solid fa-copy"></i> <span x-text="card.pages + ' pages'"></span></span>
																								<span class="inline-flex items-center gap-1"><i class="fa-regular fa-calendar"></i> <span x-text="card.updated"></span></span>
																				</div>

																				<!-- Actions: Preview (modal) + View(file_url) + Details(slug) + Download(route) — preserves backend routes -->
																				<div class="mt-auto flex flex-wrap gap-2">
																								<button @click="openPreview(card)"
																												class="px-3 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition">
																												<i class="fa-solid fa-eye mr-1"></i> Preview
																								</button>
																								<a :href="card.hasFile ? card.file_url : '#'" target="_blank"
																												:class="card.hasFile ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed pointer-events-none'"
																												class="px-3 py-2 text-sm rounded-lg transition">
																												<i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> View
																								</a>
																								<a :href="card.detail_url" x-show="card.detail_url"
																												class="px-3 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
																												Details
																								</a>
																								<a :href="card.hasFile ? card.download_url : '#'"
																												:class="card.hasFile ? 'border border-emerald-600 text-emerald-600 hover:bg-emerald-50' : 'border border-gray-200 text-gray-400 cursor-not-allowed pointer-events-none'"
																												class="px-3 py-2 text-sm rounded-lg transition">
																												<i class="fa-solid fa-download mr-1"></i> <span x-text="card.button_text || 'Download'"></span>
																								</a>
																				</div>
																</div>
												</template>
								</div>

								<!-- Pagination (4 per page, per category) -->
<div x-show="totalPages > 1" x-cloak class="mt-8 flex items-center justify-center gap-3">
	<button type="button" @click="prevPage()" :disabled="page === 1"
		:class="page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-emerald-50 hover:text-emerald-700'"
		class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition">
		<i class="fa-solid fa-chevron-left text-xs"></i> Prev
	</button>
	<span class="text-sm text-gray-500">Page
		<span class="font-semibold text-slate-700" x-text="page"></span> of
		<span class="font-semibold text-slate-700" x-text="totalPages"></span>
	</span>
	<button type="button" @click="nextPage()" :disabled="page === totalPages"
		:class="page === totalPages ? 'opacity-40 cursor-not-allowed' : 'hover:bg-emerald-50 hover:text-emerald-700'"
		class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition">
		Next <i class="fa-solid fa-chevron-right text-xs"></i>
	</button>
</div>

<!-- Empty state for search/filter no match (updated UI) -->
								<div x-show="resources.length > 0 && filteredCards.length === 0" class="text-center py-16" x-cloak>
												<i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-4"></i>
												<p class="text-gray-500">No resources match your search.</p>
								</div>
												</div>
								</div>

								{{-- No-JS fallback: the Alpine card grid requires JavaScript, so list the same resources server-side. --}}
								<noscript>
												<div class="rounded-lg border border-slate-200 bg-white p-6">
																<h2 class="text-lg font-bold text-slate-950">All resources</h2>
																<ul class="mt-4 space-y-3">
																				@forelse ($resources as $r)
																								<li class="text-sm text-slate-700">
																												<a href="{{ $r->slug ? route('resources.show', $r->slug) : $r->file_url }}" class="font-semibold text-green-700 hover:underline">{{ $r->title }}</a>
																												@if ($r->file_path)
																																<span class="block break-all text-xs text-slate-500">{{ $r->file_path }}</span>
																												@endif
																								</li>
																				@empty
																								<li class="text-sm text-slate-500">No resources have been published yet.</li>
																				@endforelse
																</ul>
												</div>
								</noscript>

								<!-- Preview Modal (updated UI) — iframe uses file_url (handles storage/public + resource/ + http) -->
								<div x-show="modalOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
												<div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()"></div>
												<div x-show="modalOpen" x-transition class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col"
																role="dialog" aria-modal="true" :aria-labelledby="'modal-title-' + (modalResource ? modalResource.slug : '')">
																<!-- Modal header -->
																<div class="flex items-start justify-between p-5 border-b border-slate-200">
																				<div>
																								<span class="text-xs font-medium text-emerald-600 uppercase tracking-wide" x-text="modalResource?.categoryLabel"></span>
																								<h2 :id="'modal-title-' + (modalResource?.slug || '')" class="text-xl font-bold text-gray-800 mt-1" x-text="modalResource?.title"></h2>
																								<div class="text-xs text-gray-500 flex flex-wrap gap-x-3 gap-y-1 mt-2">
																												<span><i class="fa-solid fa-file-pdf text-red-500 mr-1"></i> PDF</span>
																												<span x-text="modalResource?.size"></span>
																												<span x-text="modalResource?.pages ? modalResource.pages + ' pages' : ''"></span>
																												<span x-text="modalResource?.updated ? 'Updated ' + modalResource.updated : ''"></span>
																								</div>
																				</div>
																				<button @click="closeModal()" aria-label="Close preview" class="text-gray-400 hover:text-gray-700 transition ml-4">
																								<i class="fa-solid fa-xmark text-xl"></i>
																				</button>
																</div>
																<!-- Modal body -->
																<div class="flex-1 overflow-auto p-5">
																				<template x-if="modalResource && modalResource.hasFile">
																								<iframe :src="modalResource.file_url" class="w-full h-[75vh] border rounded-lg" title="Document preview"></iframe>
																				</template>
																				<template x-if="modalResource && !modalResource.hasFile">
																								<div class="space-y-4">
																												<p class="text-gray-600" x-text="modalResource?.description"></p>
																												<div class="bg-slate-50 border border-slate-200 rounded-lg p-6 space-y-3">
																																<div class="h-4 bg-slate-200 rounded w-3/4"></div>
																																<div class="h-3 bg-slate-200 rounded w-full"></div>
																																<div class="h-3 bg-slate-200 rounded w-5/6"></div>
																																<div class="h-3 bg-slate-200 rounded w-full"></div>
																																<div class="h-3 bg-slate-200 rounded w-2/3"></div>
																																<div class="h-4 bg-slate-200 rounded w-1/2 mt-6"></div>
																																<div class="h-3 bg-slate-200 rounded w-full"></div>
																																<div class="h-3 bg-slate-200 rounded w-4/5"></div>
																												</div>
																												<p class="text-sm text-gray-400 italic text-center">Full document preview available once the file is uploaded.</p>
																								</div>
																				</template>
																</div>
																<!-- Modal footer -->
																<div class="flex items-center justify-end gap-3 p-5 border-t border-slate-200">
																				<button @click="closeModal()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Close</button>
																				<template x-if="modalResource && modalResource.hasFile">
																								<a :href="modalResource.file_url" target="_blank"
																												class="px-4 py-2 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
																												<i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Open in new tab
																								</a>
																				</template>
																				<template x-if="modalResource && modalResource.hasFile">
																								<a :href="modalResource.download_url"
																												class="px-4 py-2 text-sm border border-emerald-600 text-emerald-600 rounded-lg hover:bg-emerald-50 transition">
																												<i class="fa-solid fa-download mr-1"></i> Download
																								</a>
																				</template>
																</div>
												</div>
								</div>
				</div>
@endsection

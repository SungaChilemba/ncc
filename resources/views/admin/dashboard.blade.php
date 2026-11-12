@extends('admin.layouts.app')

@section('title', 'CMS Dashboard')
@section('heading', 'CMS Dashboard')
@section('subheading', 'Publishing status, advert enquiries, and recent activity across the website.')


@section('content')
    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total items</p>
            <p class="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Published</p>
            <p class="mt-2 text-3xl font-bold text-green-700 dark:text-green-400">{{ $stats['published'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Drafts</p>
            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['drafts'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Managed pages</p>
            <p class="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{{ $stats['sections'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Media assets</p>
            <p class="mt-2 text-3xl font-bold text-slate-950 dark:text-slate-100">{{ $stats['media'] }}</p>
        </div>
    </div>

    <section class="mt-6 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
        @php
            $enquiryCounts = $enquiries['counts'] ?? [];
            $pendingCount = $enquiryCounts['pending_review'] ?? 0;
        @endphp
        <div class="flex flex-col gap-3 border-b border-slate-200 dark:border-slate-800 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-slate-100">
                    Advert enquiries
                    @if ($pendingCount)
                        <span class="rounded-full bg-amber-100 dark:bg-amber-900/50 px-2 py-0.5 text-xs font-bold text-amber-800 dark:text-amber-300">{{ $pendingCount }} awaiting review</span>
                    @endif
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Approving an enquiry publishes it as a sponsored advert on /advertise.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <span class="rounded-full bg-green-50 dark:bg-green-950/40 px-2.5 py-1 text-green-700 dark:text-green-400">{{ $enquiryCounts['approved'] ?? 0 }} live</span>
                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-slate-600 dark:text-slate-400">{{ $enquiryCounts['expired'] ?? 0 }} expired</span>
                <span class="rounded-full bg-red-50 dark:bg-red-950/40 px-2.5 py-1 text-red-700 dark:text-red-400">{{ $enquiryCounts['rejected'] ?? 0 }} rejected</span>
                <a href="{{ route('admin.contents.index', ['section' => 'advertise', 'status' => 'enquiries']) }}" class="rounded-md bg-green-700 dark:bg-green-600 px-3 py-1.5 text-white hover:bg-green-800 dark:hover:bg-green-500">Open board</a>
            </div>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($enquiries['pending'] ?? [] as $pendingEnquiry)
                @php
                    $pendingData = $pendingEnquiry->data ?? [];
                @endphp
                <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-950 dark:text-slate-100">{{ $pendingData['company'] ?? $pendingEnquiry->title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ $pendingData['reference_number'] ?? '—' }}
                            @if (! empty($pendingData['industry']))
                                · {{ $pendingData['industry'] }}
                            @endif
                            @if (! empty($pendingData['budget']))
                                · Budget {{ $pendingData['budget'] }}
                            @endif
                            · received {{ $pendingEnquiry->created_at?->diffForHumans() }}
                        </p>
                    </div>
                    <a href="{{ route('admin.enquiries.review', $pendingEnquiry->id) }}" class="w-fit rounded-md border border-green-700 dark:border-green-500 px-3 py-1.5 text-xs font-semibold text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40">Review</a>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500 dark:text-slate-400">No enquiries are waiting for review.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Website Sections</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Each page has constrained content types so editors can move quickly without breaking layouts.</p>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:border-green-600 dark:hover:border-green-500 hover:text-green-700 dark:hover:text-green-300">
                View Website
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ($sections as $managedSection)
                <article class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-950 dark:text-slate-100">{{ $managedSection['label'] }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $managedSection['description'] }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:text-slate-400">{{ $managedSection['total'] }}</span>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-green-50 dark:bg-green-950/40 px-2.5 py-1 text-green-700 dark:text-green-400">{{ $managedSection['published'] }} published</span>
                        <span class="rounded-full bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 text-amber-700 dark:text-amber-400">{{ $managedSection['drafts'] }} drafts</span>
                    </div>
                    <div class="mt-5 flex items-center gap-2">
                        <a href="{{ route('admin.contents.index', ['section' => $managedSection['key']]) }}" class="inline-flex flex-1 items-center justify-center rounded-md bg-green-700 dark:bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">
                            Manage
                        </a>
                        <a href="{{ route($managedSection['route']) }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:border-green-600 dark:hover:border-green-500 hover:text-green-700 dark:hover:text-green-300">
                            Preview
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @php
        $activityMeta = [
            'created' => ['label' => 'Created', 'icon' => 'fa-plus', 'class' => 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400'],
            'updated' => ['label' => 'Updated', 'icon' => 'fa-pen', 'class' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'],
            'published' => ['label' => 'Published', 'icon' => 'fa-circle-check', 'class' => 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400'],
            'unpublished' => ['label' => 'Unpublished', 'icon' => 'fa-circle-pause', 'class' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400'],
            'deleted' => ['label' => 'Deleted', 'icon' => 'fa-trash-can', 'class' => 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400'],
            'image_uploaded' => ['label' => 'Image uploaded', 'icon' => 'fa-image', 'class' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'],
            'publish_blocked' => ['label' => 'Publish blocked', 'icon' => 'fa-ban', 'class' => 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400'],
            'revision_restored' => ['label' => 'Revision restored', 'icon' => 'fa-clock-rotate-left', 'class' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300'],
        ];
        $activityFallback = fn (string $action) => [
            'label' => ucfirst(str_replace('_', ' ', $action)),
            'icon' => 'fa-clock-rotate-left',
            'class' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        ];
    @endphp

    <section class="mt-8 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
        <div class="border-b border-slate-200 dark:border-slate-800 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Recent activity</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Edits, publishes and deletions across the website, with who made them.</p>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($activity as $audit)
                @php
                    $meta = $activityMeta[$audit->action] ?? $activityFallback((string) $audit->action);
                    $content = $audit->content;
                    $snapshot = is_array($audit->changes) ? $audit->changes : [];
                    $title = $content?->title ?: ($snapshot['title'] ?? null);
                    $sectionKey = $content?->section ?? ($snapshot['section'] ?? null);
                    $typeKey = $content?->type ?? ($snapshot['type'] ?? null);
                    $sectionLabel = $sectionKey ? config('cms.sections.' . $sectionKey . '.label', ucfirst(str_replace('-', ' ', $sectionKey))) : null;
                    $typeLabel = $typeKey ? config('cms.types.' . $typeKey, ucfirst(str_replace('_', ' ', $typeKey))) : null;
                    $actor = $audit->user?->name ?? 'System';
                    $context = collect([$sectionLabel, $typeLabel, 'by ' . $actor])->filter()->implode(' · ');
                @endphp
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $meta['class'] }}">
                        <i class="fa-solid {{ $meta['icon'] }} text-xs" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                            <p class="truncate text-sm font-semibold {{ $content ? 'text-slate-950 dark:text-slate-100' : 'text-slate-500 dark:text-slate-400' }}">
                                @if ($content)
                                    <a href="{{ route('admin.contents.edit', ['section' => $content->section, 'id' => $content->id]) }}" class="hover:text-green-700 hover:underline dark:hover:text-green-300">{{ $title ?: 'Untitled item' }}</a>
                                @else
                                    {{ $title ?: 'Removed item' }}
                                @endif
                            </p>
                            <span class="w-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ $context }}
                            @if ($context)
                                ·
                            @endif
                            <time datetime="{{ $audit->created_at?->toIso8601String() }}" title="{{ $audit->created_at?->format('j M Y, H:i') }}">{{ $audit->created_at?->diffForHumans() }}</time>
                        </p>
                    </div>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500 dark:text-slate-400">No publishing activity yet. Edits, publishes and deletions will appear here.</p>
            @endforelse
        </div>
    </section>
@endsection

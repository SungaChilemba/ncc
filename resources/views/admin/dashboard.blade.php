@extends('admin.layouts.app')

@section('title', 'CMS Dashboard')
@section('heading', 'CMS Dashboard')
@section('subheading', 'Manage website sections, publishing status, and recent updates from one place.')

@section('content')
    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total items</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Published</p>
            <p class="mt-2 text-3xl font-bold text-green-700">{{ $stats['published'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Drafts</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['drafts'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Managed pages</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['sections'] }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Media assets</p>
            <p class="mt-2 text-3xl font-bold text-slate-950">{{ $stats['media'] }}</p>
        </div>
    </div>

    <section class="mt-8">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">Website Sections</h2>
                <p class="text-sm text-slate-500">Each page has constrained content types so editors can move quickly without breaking layouts.</p>
            </div>
            <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">
                View Website
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @foreach ($sections as $managedSection)
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-base font-semibold text-slate-950">{{ $managedSection['label'] }}</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $managedSection['description'] }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $managedSection['total'] }}</span>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-green-50 px-2.5 py-1 text-green-700">{{ $managedSection['published'] }} published</span>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">{{ $managedSection['drafts'] }} drafts</span>
                    </div>
                    <div class="mt-5 flex items-center gap-2">
                        <a href="{{ route('admin.contents.index', ['section' => $managedSection['key']]) }}" class="inline-flex flex-1 items-center justify-center rounded-md bg-green-700 px-3 py-2 text-sm font-semibold text-white hover:bg-green-800">
                            Manage
                        </a>
                        <a href="{{ route($managedSection['route']) }}" target="_blank" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">
                            Preview
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-8 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-950">Publishing Audit</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($audits as $audit)
                <div class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-slate-950">{{ ucfirst(str_replace('_', ' ', $audit->action)) }}</p>
                    <p class="text-xs text-slate-500">{{ $audit->created_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500">No publishing activity has been recorded yet.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-950">Recent Activity</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recent as $item)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-950">{{ $item->title ?: ucfirst($item->type) . ' item' }}</p>
                        <p class="text-xs text-slate-500">{{ config('cms.sections.' . $item->section . '.label', ucfirst($item->section)) }} - {{ config('cms.types.' . $item->type, ucfirst($item->type)) }} - Updated {{ $item->updated_at?->diffForHumans() }}</p>
                    </div>
                    <span class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->is_published ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $item->is_published ? 'Published' : 'Draft' }}
                    </span>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500">No CMS activity yet.</p>
            @endforelse
        </div>
    </section>
@endsection

@extends('admin.layouts.app')

@php
    $currentStatus = request('status', 'all');
    $currentType = request('type', '');
    $statusLinks = [
        'all' => 'All',
        'published' => 'Published',
        'draft' => 'Drafts',
    ];
    $supportsSubmissions = in_array('submission', $sectionConfig['types'], true);

    if ($supportsSubmissions) {
        $statusLinks['private'] = 'Private';
        $statusLinks['enquiries'] = 'Enquiries';
    }
@endphp

@section('title', 'Manage ' . $sectionConfig['label'])
@section('heading', $sectionConfig['label'])
@section('subheading', $sectionConfig['description'])

@section('content')
    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-semibold text-slate-950">All {{ $sectionConfig['label'] }}</h2>
                <a href="{{ route('admin.contents.create', ['section' => $section, 'type' => request('type')]) }}" class="inline-flex items-center rounded-md border border-green-700 px-3 py-1.5 text-sm font-semibold text-green-700 hover:bg-green-50">
                    Add New
                </a>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                @foreach ($statusLinks as $statusKey => $statusLabel)
                    <a href="{{ route('admin.contents.index', ['section' => $section] + ($statusKey === 'all' ? request()->except('status', 'page') : ['status' => $statusKey] + request()->except('status', 'page'))) }}" class="{{ $currentStatus === $statusKey || ($statusKey === 'all' && ! request('status')) ? 'font-semibold text-green-700' : 'text-slate-600 hover:text-green-700' }}">
                        {{ $statusLabel }}
                    </a>
                    @if (! $loop->last)
                        <span class="text-slate-300">|</span>
                    @endif
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('admin.contents.index', ['section' => $section]) }}" class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
            @if ($currentStatus !== 'all')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            @if ($currentType)
                <input type="hidden" name="type" value="{{ $currentType }}">
            @endif
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($sectionConfig['label']) }}" class="min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100 sm:w-72">
            <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">Search</button>
        </form>
    </div>

    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-2 sm:flex-row">
            <select form="bulk-content-form" name="action" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="publish">Publish</option>
                <option value="unpublish">Unpublish</option>
                <option value="delete">Delete</option>
            </select>
            <button form="bulk-content-form" type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">Apply</button>
        </div>

        <form method="GET" action="{{ route('admin.contents.index', ['section' => $section]) }}" class="grid gap-2 sm:grid-cols-[12rem_12rem_auto]">
            @if (request('q'))
                <input type="hidden" name="q" value="{{ request('q') }}">
            @endif
            <select name="type" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">All types</option>
                @foreach ($typeOptions as $typeKey => $typeLabel)
                    <option value="{{ $typeKey }}" @selected($currentType === $typeKey)>{{ $typeLabel }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option value="published" @selected($currentStatus === 'published')>Published</option>
                <option value="draft" @selected($currentStatus === 'draft')>Draft</option>
                @if ($supportsSubmissions)
                    <option value="private" @selected($currentStatus === 'private')>Private</option>
                    <option value="enquiries" @selected($currentStatus === 'enquiries')>Enquiries</option>
                @endif
            </select>
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
        </form>
    </div>

    <form id="bulk-content-form" method="POST" action="{{ route('admin.contents.bulk', ['section' => $section]) }}" class="hidden">
        @csrf
    </form>
    <form id="reorder-content-form" method="POST" action="{{ route('admin.contents.reorder', ['section' => $section]) }}" class="hidden">
        @csrf
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="w-12 px-4 py-3">
                            <input type="checkbox" class="rounded border-slate-300 text-green-700 focus:ring-green-600" data-select-all>
                        </th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="w-28 px-4 py-3">Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($contents as $item)
                        <tr class="group align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <input form="bulk-content-form" type="checkbox" name="ids[]" value="{{ $item->id }}" class="rounded border-slate-300 text-green-700 focus:ring-green-600" data-row-select>
                            </td>
                            <td class="min-w-[26rem] px-4 py-4">
                                <div class="flex gap-3">
                                    @if ($item->image_path)
                                        <img src="{{ $item->image_url }}?v={{ $item->updated_at->timestamp }}" alt="{{ $item->alt_text ?: $item->title }}" class="h-14 w-20 rounded-md object-cover">
                                    @else
                                        <div class="flex h-14 w-20 items-center justify-center rounded-md bg-slate-100 text-xs font-semibold text-slate-400">No image</div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.contents.edit', ['section' => $section, 'id' => $item->id] + request()->query()) }}" class="text-base font-semibold text-slate-950 hover:text-green-700">
                                            {{ $item->title ?: 'Untitled item' }}
                                        </a>
                                        @if ($item->subtitle)
                                            <p class="mt-1 text-xs font-medium text-slate-500">{{ $item->subtitle }}</p>
                                        @endif
                                        @if ($item->description)
                                            <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">{{ str($item->description)->squish()->limit(120) }}</p>
                                        @endif
                                        @if ($section === 'advertise' && $item->type === 'submission' && !empty($item->data))
                                            <div class="mt-2 grid gap-1.5 rounded-md border border-amber-200 bg-amber-50/60 p-2.5 text-xs">
                                                @if (!empty($item->data['contact_name']))
                                                    <div class="flex flex-wrap gap-x-3"><span class="font-semibold text-slate-700">Contact:</span><span class="text-slate-800">{{ $item->data['contact_name'] }}</span>
                                                        @if (!empty($item->data['contact_email']))
                                                            <a href="mailto:{{ $item->data['contact_email'] }}" class="font-medium text-green-700 hover:underline">{{ $item->data['contact_email'] }}</a>
                                                        @endif
                                                        @if (!empty($item->data['contact_phone']))
                                                            <a href="tel:{{ $item->data['contact_phone'] }}" class="text-slate-600">{{ $item->data['contact_phone'] }}</a>
                                                        @endif
                                                    </div>
                                                @endif
                                                <div class="flex flex-wrap gap-x-3 gap-y-1 text-slate-600">
                                                    @if (!empty($item->data['company']))<span>Company: <span class="font-medium text-slate-800">{{ $item->data['company'] }}</span></span>@endif
                                                    @if (!empty($item->data['industry']))<span>· {{ $item->data['industry'] }}</span>@endif
                                                    @if (!empty($item->data['budget']))<span>· Budget: {{ $item->data['budget'] }}</span>@endif
                                                    @if (!empty($item->data['start_date']))<span>· Start: {{ \Carbon\Carbon::parse($item->data['start_date'])->format('j M Y') }}</span>@endif
                                                    @if (!empty($item->data['preferred_contact']))<span>· Pref: {{ $item->data['preferred_contact'] }}</span>@endif
                                                    @if (!empty($item->data['reference_number']))<span class="ml-auto rounded bg-white px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700 border">{{ $item->data['reference_number'] }}</span>@endif
                                                </div>
                                                @if (!empty($item->data['artwork']['url'] ?? $item->data['artwork']['path'] ?? null))
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-semibold text-slate-700">Artwork:</span>
                                                        <a href="{{ $item->data['artwork']['url'] ?? Storage::disk('public')->url($item->data['artwork']['path']) }}" target="_blank" class="text-green-700 hover:underline truncate max-w-[18rem]">{{ $item->data['artwork']['original_name'] ?? basename($item->data['artwork']['path']) }}</a>
                                                        @if (!empty($item->data['artwork']['size']))<span class="text-slate-400">({{ round($item->data['artwork']['size']/1024) }} KB)</span>@endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-xs font-semibold opacity-100 transition group-hover:opacity-100 md:opacity-0">
                                            <a href="{{ route('admin.contents.edit', ['section' => $section, 'id' => $item->id] + request()->query()) }}" class="text-green-700 hover:underline">Edit</a>
                                            <span class="text-slate-300">|</span>
                                            <a href="{{ route('admin.contents.preview', ['section' => $section, 'id' => $item->id]) }}" class="text-blue-700 hover:underline">Draft Preview</a>
                                            @if ($item->public_url)
                                                <span class="text-slate-300">|</span>
                                                <a href="{{ $item->public_url }}" target="_blank" class="text-slate-600 hover:underline">View</a>
                                            @endif
                                            @if ($item->type === 'resource' && $item->slug && $item->file_path)
                                                <span class="text-slate-300">|</span>
                                                <a href="{{ route('resources.download', $item->slug) }}" class="text-emerald-700 hover:underline">Download</a>
                                            @endif
                                            @if ($item->type !== 'submission')
                                                <span class="text-slate-300">|</span>
                                                <button form="publish-form-{{ $item->id }}" type="submit" class="text-blue-700 hover:underline">
                                                    {{ $item->is_published ? 'Unpublish' : 'Publish' }}
                                                </button>
                                            @endif
                                            <span class="text-slate-300">|</span>
                                            <button form="delete-form-{{ $item->id }}" type="submit" class="text-red-700 hover:underline">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ config('cms.types.' . $item->type, ucfirst($item->type)) }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $item->category ?: '-' }}</td>
                            <td class="px-4 py-4 text-slate-600">
                                @if ($item->published_on)
                                    {{ $item->published_on->format('j M Y') }}
                                @elseif ($item->published_at)
                                    {{ $item->published_at->format('j M Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->type === 'submission' ? 'bg-slate-100 text-slate-700' : ($item->is_published ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700') }}">
                                    {{ $item->type === 'submission' ? 'Private' : ($item->is_published ? 'Published' : 'Draft') }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <input form="reorder-content-form" type="number" name="orders[{{ $item->id }}]" min="0" value="{{ $item->sort_order }}" class="w-20 rounded-md border border-slate-300 px-2 py-1 text-sm">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500">
                                No content matches this view.
                                <a href="{{ route('admin.contents.create', ['section' => $section]) }}" class="font-semibold text-green-700 hover:underline">Add the first item</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach ($contents as $item)
            @if ($item->type !== 'submission')
                <form id="publish-form-{{ $item->id }}" method="POST" action="{{ route('admin.contents.toggle-publish', ['section' => $section, 'id' => $item->id]) }}" class="hidden">
                    @csrf
                </form>
            @endif
            <form id="delete-form-{{ $item->id }}" method="POST" action="{{ route('admin.contents.destroy', ['section' => $section, 'id' => $item->id]) }}" class="hidden" onsubmit="return confirm('Delete this content item?');">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <button form="reorder-content-form" type="submit" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">
                Save Order
            </button>
            @if ($contents->hasPages())
                <div>
                    {{ $contents->links() }}
                </div>
            @else
                <p class="text-sm text-slate-500">{{ $contents->total() }} item(s)</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const selectAll = document.querySelector('[data-select-all]');
            const rowSelects = [...document.querySelectorAll('[data-row-select]')];

            selectAll?.addEventListener('change', () => {
                rowSelects.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });
        });
    </script>
@endpush

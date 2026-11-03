@extends('admin.layouts.app')

@php
    $currentStatus = request('status', '');
    $statusClasses = [
        'received' => 'bg-slate-100 text-slate-700',
        'triage' => 'bg-amber-50 text-amber-700',
        'investigation' => 'bg-blue-50 text-blue-700',
        'referred' => 'bg-purple-50 text-purple-700',
        'resolved' => 'bg-green-50 text-green-700',
        'closed' => 'bg-slate-800 text-white',
        'duplicate' => 'bg-slate-100 text-slate-500',
        'withdrawn' => 'bg-slate-100 text-slate-500',
    ];
@endphp

@section('title', 'Case Queue')
@section('heading', 'Case Queue')
@section('subheading', 'Every case you are authorised to see, newest first.')

@section('content')
    @if (! empty($recoveryCodes))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-5 text-sm text-green-900">
            <p class="font-semibold">Save your recovery codes now</p>
            <p class="mt-1">Each code can be used once if you lose access to your authenticator app. They are not shown again.</p>
            <ul class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                @foreach ($recoveryCodes as $recoveryCode)
                    <li class="select-all rounded bg-white px-2 py-1 text-center font-semibold tracking-wider">{{ $recoveryCode }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.cases.index', request()->except('status', 'page')) }}"
               class="{{ $currentStatus === '' ? 'rounded-full bg-green-700 px-3 py-1 font-semibold text-white' : 'rounded-full border border-slate-200 bg-white px-3 py-1 font-semibold text-slate-600 hover:border-green-600 hover:text-green-700' }}">
                All <span class="ml-1 text-xs">{{ $counts->sum() }}</span>
            </a>
            @foreach (\App\Models\Report::STATUSES as $statusKey => $statusLabel)
                <a href="{{ route('admin.cases.index', ['status' => $statusKey] + request()->except('status', 'page')) }}"
                   class="{{ $currentStatus === $statusKey ? 'rounded-full bg-green-700 px-3 py-1 font-semibold text-white' : 'rounded-full border border-slate-200 bg-white px-3 py-1 font-semibold text-slate-600 hover:border-green-600 hover:text-green-700' }}">
                    {{ $statusLabel }} <span class="ml-1 text-xs">{{ $counts[$statusKey] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <form method="GET" action="{{ route('admin.cases.index') }}" class="mb-5 grid gap-2 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_10rem_10rem_auto]">
        @if ($currentStatus)
            <input type="hidden" name="status" value="{{ $currentStatus }}">
        @endif
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search reference, district or village"
               class="min-w-0 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
        <select name="priority" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">All priorities</option>
            <option value="urgent" @selected(request('priority') === 'urgent')>Urgent</option>
            <option value="standard" @selected(request('priority') === 'standard')>Standard</option>
        </select>
        <select name="assigned" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
            <option value="">Anyone</option>
            <option value="me" @selected(request('assigned') === 'me')>Assigned to me</option>
            <option value="unassigned" @selected(request('assigned') === 'unassigned')>Unassigned</option>
        </select>
        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filter</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Received</th>
                        <th class="px-4 py-3">Violation</th>
                        <th class="px-4 py-3">District</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Assigned</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold text-slate-900">{{ $report->reference_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $report->received_at?->format('j M Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $report->violation_type }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $report->district }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $report->priority === 'urgent' ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($report->priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $report->statusLabel() }}
                                </span>
                                @if ($report->isAnonymised())
                                    <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">Anonymised</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $report->assignee?->name ?? 'Unassigned' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('admin.cases.show', $report) }}" class="rounded-md border border-green-700 px-3 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-50">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-slate-500">No cases match this view.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $reports->links() }}
    </div>
@endsection

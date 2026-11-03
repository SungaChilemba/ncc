@extends('admin.layouts.app')

@section('title', 'Access Audit')
@section('heading', 'Access Audit')
@section('subheading', 'Every user, role, and permission change in the admin area, newest first.')

@section('content')
    <form method="GET" action="{{ route('admin.access.audit') }}" class="mb-5 flex flex-col gap-2 sm:flex-row">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search summary or action"
               class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100 sm:w-72">
        <select name="action" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">Filter</button>
        @if (request()->filled('q') || request()->filled('action'))
            <a href="{{ route('admin.access.audit') }}" class="px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-900">Clear</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Summary</th>
                        <th class="px-4 py-3">IP hash</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                {{ $log->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($log->actor)
                                    <span class="font-semibold text-slate-900">{{ $log->actor->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $log->actor->email }}</span>
                                @else
                                    <span class="text-slate-400">System</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700">{{ $log->action }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $log->summary }}
                                @if ($log->changes)
                                    <details class="mt-1 text-xs text-slate-500">
                                        <summary class="cursor-pointer font-semibold text-green-700">Details</summary>
                                        <pre class="mt-2 overflow-x-auto rounded-md bg-slate-50 p-2 text-xs">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-400">
                                {{ $log->ip_hash ? \Illuminate\Support\Str::limit($log->ip_hash, 12, '…') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No access-control changes have been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $logs->links() }}</div>

    <p class="mt-4 text-xs text-slate-500">
        IP addresses are stored as keyed hashes so the trail can spot repeated sources without keeping raw addresses.
    </p>
@endsection

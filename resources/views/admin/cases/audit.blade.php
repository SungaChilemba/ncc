@extends('admin.layouts.app')

@section('title', 'Access trail ' . $report->reference_number)
@section('heading', 'Access trail')
@section('subheading', 'Append-only record of who opened or changed this case, and when.')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.cases.show', $report) }}" class="text-sm font-semibold text-green-700 hover:underline">&larr; Back to case</a>
        <span class="rounded-full bg-slate-100 px-3 py-1 font-mono text-sm font-semibold text-slate-700">{{ $report->reference_number }}</span>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">When</th>
                        <th class="px-4 py-3">Who</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Fields</th>
                        <th class="px-4 py-3">Reason / reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $log->created_at?->format('j M Y, H:i:s') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $log->user?->name ?? 'Reporter / public' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline($log->action) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ implode(', ', $log->fields_accessed ?? []) ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No access has been recorded for this case.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">
        {{ $logs->links() }}
    </div>

    <p class="mt-4 text-xs text-slate-500">IP addresses and browser identifiers are stored only as keyed hashes. Reasons may include a filename when evidence was downloaded.</p>
@endsection

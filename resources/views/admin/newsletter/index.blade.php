@extends('admin.layouts.app')

@section('title', 'Newsletter')
@section('heading', 'Newsletter Subscribers')
@section('subheading', 'People who asked to receive updates from the NCC website footer.')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.newsletter.index') }}" class="flex w-full max-w-md gap-2">
            <label for="newsletter-search" class="sr-only">Search subscribers</label>
            <input id="newsletter-search" type="search" name="q" value="{{ request('q') }}"
                placeholder="Search by email"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
            <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Search</button>
        </form>

        <a href="{{ route('admin.newsletter.export') }}"
            class="inline-flex items-center justify-center rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
            Export CSV
        </a>
    </div>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Email</th>
                        <th scope="col" class="px-5 py-3">Source</th>
                        <th scope="col" class="px-5 py-3">Subscribed</th>
                        <th scope="col" class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($subscribers as $subscriber)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-950">{{ $subscriber->email }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ ucfirst($subscriber->source) }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ optional($subscriber->created_at)->format('d M Y, H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST" action="{{ route('admin.newsletter.destroy', $subscriber) }}"
                                    onsubmit="return confirm('Remove this subscriber?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-800">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">No newsletter subscribers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscribers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $subscribers->links() }}
            </div>
        @endif
    </section>
@endsection

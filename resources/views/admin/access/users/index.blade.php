@extends('admin.layouts.app')

@section('title', 'Users & Access')
@section('heading', 'Users & Access')
@section('subheading', 'Create staff accounts and give each person the roles they need.')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.access.users.index') }}" class="flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email"
                   class="w-64 rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
            <button type="submit" class="rounded-md border border-slate-300 dark:border-slate-700 px-4 py-2 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:border-green-600 dark:hover:border-green-500 hover:text-green-700 dark:hover:text-green-300">Search</button>
        </form>

        @if ($canManage)
            <a href="{{ route('admin.access.users.create') }}" class="rounded-md bg-green-700 dark:bg-green-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">New user</a>
        @endif
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Roles</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-xs text-amber-700 dark:text-amber-400">No role assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->is_active ? 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-400' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('admin.access.users.edit', $user) }}"
                                       class="rounded-md border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:border-green-600 dark:hover:border-green-500 hover:text-green-700 dark:hover:text-green-300">
                                        {{ $canManage ? 'Edit' : 'View' }}
                                    </a>

                                    @if ($canManage)
                                        <form method="POST" action="{{ route('admin.access.users.toggle-active', $user) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md border px-3 py-1.5 text-xs font-semibold {{ $user->is_active ? 'border-red-200 dark:border-red-900/60 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40' : 'border-green-200 dark:border-green-900/60 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-950/40' }}">
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        @if ($user->hasConfirmedTwoFactor())
                                            <form method="POST" action="{{ route('admin.access.users.reset-two-factor', $user) }}"
                                                  onsubmit="return confirm('Reset two-factor authentication for {{ $user->email }}? They will need to enrol again.')">
                                                @csrf
                                                <button type="submit" class="rounded-md border border-amber-200 dark:border-amber-900/60 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40">Reset 2FA</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">No users match this search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5">{{ $users->links() }}</div>

    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
        Users with case permissions must confirm an authenticator code on every new session.
        Deactivating an account signs the person out immediately.
    </p>
@endsection

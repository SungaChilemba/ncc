@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')
@section('heading', 'Roles & Permissions')
@section('subheading', 'Create roles and choose exactly what each one can do. Permissions are combined per user.')

@section('content')
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="max-w-2xl text-sm text-slate-600">
            A user can hold several roles. The available permissions come from a catalog that is fixed in the
            application, so a new capability still needs a code release. New roles take effect immediately.
        </p>

        <a href="{{ route('admin.access.roles.create') }}" class="rounded-md bg-green-700 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-green-800">New role</a>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Case access</th>
                        <th class="px-4 py-3">Permissions</th>
                        <th class="px-4 py-3">Users</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-900">{{ $role->name }}</span>
                                    @if ($role->is_system)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Built-in</span>
                                    @endif
                                    @if ($role->requires_totp)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">2FA</span>
                                    @endif
                                </div>
                                <p class="mt-1 max-w-md text-xs text-slate-500">{{ $role->description }}</p>
                                <p class="mt-1 font-mono text-xs text-slate-400">{{ $role->key }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                {{ \App\Models\User::CASE_SCOPES[$role->case_scope] ?? $role->case_scope }}
                                @if ($role->can_be_assigned)
                                    <span class="mt-1 block text-xs text-slate-400">Can be assigned cases</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <span class="font-semibold text-slate-900">{{ $role->permissions->count() }}</span>
                                @if ($role->permissions->where('is_dangerous', true)->isNotEmpty())
                                    <span class="mt-1 block text-xs text-amber-700">
                                        {{ $role->permissions->where('is_dangerous', true)->count() }} sensitive
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $role->users_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('admin.access.roles.edit', $role) }}"
                                       class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-green-600 hover:text-green-700">Edit</a>

                                    @unless ($role->is_system)
                                        <form method="POST" action="{{ route('admin.access.roles.destroy', $role) }}"
                                              onsubmit="return confirm('Delete the role “{{ $role->name }}”? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No roles exist yet. Run the access-control seeder or create a role.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($systemRole)
        <p class="mt-4 text-xs text-slate-500">
            The <strong>{{ $systemRole->name }}</strong> role is protected: it always receives every administration
            permission and cannot be deleted or given case-data access. Grant a separate case role when someone needs
            to work on cases.
        </p>
    @endif
@endsection

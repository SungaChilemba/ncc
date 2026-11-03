@extends('admin.layouts.app')

@php
    $isEdit = $user->exists;
    $selectedRoles = old('roles', $user->roles->pluck('id')->all());
    $roleGroups = $roles->groupBy('category');
    $groupLabels = [
        'admin' => 'Administration',
        'cms' => 'Website content',
        'case' => 'Case management',
        'custom' => 'Custom roles',
    ];
@endphp

@section('title', $isEdit ? 'Edit user' : 'New user')
@section('heading', $isEdit ? 'Edit user' : 'New user')
@section('subheading', $isEdit ? 'Update account details and role assignments.' : 'Create an account and choose the roles it should hold.')

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('admin.access.users.update', $user) : route('admin.access.users.store') }}"
          class="max-w-3xl space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        @unless ($canManage)
            <div class="rounded-md border border-slate-300 bg-slate-100 p-4 text-sm text-slate-700">
                You can review this account, but changing users requires the “Manage users” permission.
            </div>
        @endunless

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Account</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800">Full name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required @disabled(! $canManage)
                           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required @disabled(! $canManage)
                           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-800">
                        {{ $isEdit ? 'New password (optional)' : 'Password' }}
                    </label>
                    <input id="password" name="password" type="password" autocomplete="new-password" @required(! $isEdit) @disabled(! $canManage)
                           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-800">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @disabled(! $canManage)
                           class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">
                </div>
            </div>

            @if ($isEdit)
                <p class="mt-4 text-xs text-slate-500">
                    Status: <strong>{{ $user->is_active ? 'Active' : 'Inactive' }}</strong>.
                    Use the activate/deactivate action on the user list to change it.
                </p>
            @endif
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">Roles</h2>
            <p class="mt-1 text-sm text-slate-500">A user can hold more than one role. Permissions are combined; the widest case scope wins.</p>

            @error('roles')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

            <div class="mt-4 space-y-5">
                @foreach ($groupLabels as $category => $label)
                    @if ($roleGroups->has($category))
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                @foreach ($roleGroups[$category] as $role)
                                    <label class="flex items-start gap-3 rounded-md border border-slate-200 p-3 text-sm hover:border-green-400">
                                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                               @checked(in_array($role->id, $selectedRoles)) @disabled(! $canManage)
                                               class="mt-0.5 rounded border-slate-300 text-green-700 focus:ring-green-200">
                                        <span>
                                            <span class="block font-semibold text-slate-900">{{ $role->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $role->description }}</span>
                                            <span class="mt-1 block text-xs text-slate-400">
                                                {{ $role->case_scope === 'none' ? 'No case access' : \App\Models\User::CASE_SCOPES[$role->case_scope] }}
                                                @if ($role->requires_totp) · requires 2FA @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

        @if ($canManage)
            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-md bg-green-700 px-5 py-2 font-semibold text-white hover:bg-green-800">
                    {{ $isEdit ? 'Save changes' : 'Create user' }}
                </button>
                <a href="{{ route('admin.access.users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</a>
            </div>
        @else
            <a href="{{ route('admin.access.users.index') }}" class="text-sm font-semibold text-green-700 hover:underline">&larr; Back to users</a>
        @endif
    </form>
@endsection

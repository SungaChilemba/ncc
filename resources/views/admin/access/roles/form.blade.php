@extends('admin.layouts.app')

@php
    $isEdit = $role->exists;
    $selected = old('permissions', $role->permissions->pluck('id')->all());
    $groupLabels = [
        'admin' => 'Administration',
        'cms' => 'Website content',
        'cases' => 'Case management',
    ];
    $caseScopes = \App\Models\User::CASE_SCOPES;
    $totpLocked = $protected || ($isEdit && $role->hasCasePermissions());
@endphp

@section('title', $isEdit ? 'Edit role' : 'New role')
@section('heading', $isEdit ? 'Edit role' : 'New role')
@section('subheading', $isEdit ? 'Change the permissions and case scope for this role.' : 'Name the role, choose its case scope, and tick the permissions it grants.')

@section('content')
    <form method="POST"
          action="{{ $isEdit ? route('admin.access.roles.update', $role) : route('admin.access.roles.store') }}"
          class="max-w-4xl space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        @if ($protected)
            <div class="rounded-md border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 p-4 text-sm text-amber-900 dark:text-amber-200">
                This is the protected system administrator role. Permissions are fixed to the administration set and
                case access stays disabled. You can still rename it or update its description.
            </div>
        @endif

        <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Role details</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Role name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" required
                           class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                    @error('name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="key" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Internal key</label>
                    @if ($isEdit)
                        <input id="key" type="text" value="{{ $role->key }}" disabled
                               class="mt-1 w-full rounded-md border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 px-3 py-2 font-mono text-sm text-slate-500 dark:text-slate-400">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">The key is permanent once the role is created.</p>
                    @else
                        <input id="key" name="key" type="text" value="{{ old('key') }}" placeholder="Generated from the name"
                               class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 font-mono text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lowercase letters, numbers, and underscores. Leave blank to generate one.</p>
                        @error('key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Description</label>
                    <textarea id="description" name="description" rows="2"
                              class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">{{ old('description', $role->description) }}</textarea>
                    @error('description')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                @unless ($isEdit)
                    <div>
                        <label for="category" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Category</label>
                        <select id="category" name="category" class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                            @foreach (['custom' => 'Custom', 'cms' => 'Website content', 'case' => 'Case management', 'admin' => 'Administration'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $role->category ?? 'custom') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>
                @endunless

                <div>
                    <label for="case_scope" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">Case data scope</label>
                    <select id="case_scope" name="case_scope" @disabled($protected)
                            class="mt-1 w-full rounded-md border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm focus:border-green-600 dark:focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100 dark:focus:ring-green-900/40">
                        @foreach ($caseScopes as $value => $label)
                            <option value="{{ $value }}" @selected(old('case_scope', $role->case_scope ?? 'none') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Any case permission requires a scope other than “No case access”.</p>
                    @error('case_scope')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4 space-y-2">
                <label class="flex items-center gap-3 text-sm text-slate-800 dark:text-slate-200">
                    <input type="checkbox" name="can_be_assigned" value="1" @checked(old('can_be_assigned', $role->can_be_assigned)) @disabled($protected)
                           class="rounded border-slate-300 dark:border-slate-700 text-green-700 dark:text-green-400 focus:ring-green-200 dark:focus:ring-green-900/50">
                    This role can be assigned cases
                </label>
                <label class="flex items-center gap-3 text-sm text-slate-800 dark:text-slate-200">
                    <input type="checkbox" name="requires_totp" value="1" @checked(old('requires_totp', $role->requires_totp)) @disabled($totpLocked)
                           class="rounded border-slate-300 dark:border-slate-700 text-green-700 dark:text-green-400 focus:ring-green-200 dark:focus:ring-green-900/50">
                    Require two-factor authentication for this role
                </label>
                @if ($totpLocked && ! $protected)
                    <p class="text-xs text-amber-700 dark:text-amber-400">Two-factor authentication is mandatory because this role grants case permissions.</p>
                @endif
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-slate-100">Permissions</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Select what this role may do. Sensitive permissions are marked.</p>

            @error('permissions')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

            <div class="mt-4 space-y-6">
                @foreach ($permissions as $group => $items)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $groupLabels[$group] ?? ucfirst($group) }}</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach ($items as $permission)
                                <label class="flex items-start gap-3 rounded-md border border-slate-200 dark:border-slate-800 p-3 text-sm hover:border-green-400 dark:hover:border-green-500">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                           @checked(in_array($permission->id, $selected)) @disabled($protected)
                                           class="mt-0.5 rounded border-slate-300 dark:border-slate-700 text-green-700 dark:text-green-400 focus:ring-green-200 dark:focus:ring-green-900/50">
                                    <span>
                                        <span class="flex items-center gap-2 font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $permission->label }}
                                            @if ($permission->is_dangerous)
                                                <span class="rounded-full bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-400">Sensitive</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ $permission->description }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-green-700 dark:bg-green-600 px-5 py-2 font-semibold text-white hover:bg-green-800 dark:hover:bg-green-500">
                {{ $isEdit ? 'Save role' : 'Create role' }}
            </button>
            <a href="{{ route('admin.access.roles.index') }}" class="text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">Cancel</a>
        </div>
    </form>
@endsection

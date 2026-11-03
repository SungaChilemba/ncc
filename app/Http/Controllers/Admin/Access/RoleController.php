<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessAudit;
use App\Support\AccessCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManage($request);

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.access.roles.index', [
            'roles' => $roles,
            'systemRole' => Role::where('key', 'system_admin')->first(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManage($request);

        return view('admin.access.roles.form', [
            'role' => new Role(['case_scope' => 'none', 'is_active' => true]),
            'permissions' => $this->groupedPermissions(),
            'protected' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        $request->merge([
            'key' => $request->filled('key')
                ? Str::slug((string) $request->input('key'), '_')
                : Str::slug((string) $request->input('name'), '_'),
        ]);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', 'unique:roles,key'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', Rule::in(['cms', 'case', 'admin', 'custom'])],
            'case_scope' => ['required', Rule::in(array_keys(User::CASE_SCOPES))],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        [$permissionKeys, $requiresTotp] = $this->normalizePermissions($request, $validated);

        if ($permissionKeys === null) {
            return back()->withErrors([
                'case_scope' => 'Case permissions require a case scope of "Only assigned cases" or "All cases".',
            ])->withInput();
        }

        $role = Role::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'case_scope' => $validated['case_scope'],
            'can_be_assigned' => $request->boolean('can_be_assigned'),
            'requires_totp' => $requiresTotp,
            'is_system' => false,
            'is_active' => true,
        ]);

        $role->permissions()->sync(
            Permission::whereIn('key', $permissionKeys)->pluck('id'),
        );

        AccessAudit::log(
            $request->user(),
            'role.created',
            $role,
            "Created role {$role->name}",
            [
                'permissions' => $permissionKeys,
                'case_scope' => $role->case_scope,
                'requires_totp' => $role->requires_totp,
            ],
            $request,
        );

        return redirect()
            ->route('admin.access.roles.index')
            ->with('status', "Role \"{$role->name}\" created.");
    }

    public function edit(Request $request, Role $role)
    {
        $this->authorizeManage($request);

        return view('admin.access.roles.form', [
            'role' => $role->load('permissions'),
            'permissions' => $this->groupedPermissions(),
            'protected' => $role->key === 'system_admin',
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeManage($request);

        // The protected role form disables its fixed fields, so restore the
        // server-side values before validating.
        if ($role->key === 'system_admin') {
            $request->merge(['case_scope' => 'none']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'case_scope' => ['required', Rule::in(array_keys(User::CASE_SCOPES))],
            'permissions' => ['array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $before = [
            'name' => $role->name,
            'case_scope' => $role->case_scope,
            'can_be_assigned' => $role->can_be_assigned,
            'requires_totp' => $role->requires_totp,
            'permissions' => $role->permissions->pluck('key')->sort()->values()->all(),
        ];

        if ($role->key === 'system_admin') {
            $permissionKeys = AccessCatalog::roles()['system_admin']['permissions'];
            $requiresTotp = false;
            $validated['case_scope'] = 'none';
            $request->merge(['can_be_assigned' => false]);
        } else {
            [$permissionKeys, $requiresTotp] = $this->normalizePermissions($request, $validated);

            if ($permissionKeys === null) {
                return back()->withErrors([
                    'case_scope' => 'Case permissions require a case scope of "Only assigned cases" or "All cases".',
                ])->withInput();
            }
        }

        $role->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'case_scope' => $validated['case_scope'],
            'can_be_assigned' => $request->boolean('can_be_assigned'),
            'requires_totp' => $requiresTotp,
        ])->save();

        $role->permissions()->sync(
            Permission::whereIn('key', $permissionKeys)->pluck('id'),
        );

        $role->refresh()->load('permissions');

        AccessAudit::log(
            $request->user(),
            'role.updated',
            $role,
            "Updated role {$role->name}",
            [
                'before' => $before,
                'after' => [
                    'name' => $role->name,
                    'case_scope' => $role->case_scope,
                    'can_be_assigned' => $role->can_be_assigned,
                    'requires_totp' => $role->requires_totp,
                    'permissions' => $role->permissions->pluck('key')->sort()->values()->all(),
                ],
            ],
            $request,
        );

        return redirect()
            ->route('admin.access.roles.index')
            ->with('status', "Role \"{$role->name}\" updated.");
    }

    public function destroy(Request $request, Role $role)
    {
        $this->authorizeManage($request);

        if ($role->is_system) {
            return back()->withErrors([
                'role' => 'Built-in roles cannot be deleted. Create a custom role instead.',
            ]);
        }

        if ($role->users()->exists()) {
            return back()->withErrors([
                'role' => 'Reassign the users on this role before deleting it.',
            ]);
        }

        $permissionKeys = $role->permissions->pluck('key')->all();

        $role->delete();

        AccessAudit::log(
            $request->user(),
            'role.deleted',
            $role,
            "Deleted role {$role->name}",
            ['permissions' => $permissionKeys],
            $request,
        );

        return redirect()
            ->route('admin.access.roles.index')
            ->with('status', "Role \"{$role->name}\" deleted.");
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermission('admin.roles.manage'), 403);
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function groupedPermissions()
    {
        return Permission::query()
            ->orderBy('label')
            ->get()
            ->groupBy('group')
            ->sortKeys();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: list<string>|null, 1: bool}
     */
    private function normalizePermissions(Request $request, array $validated): array
    {
        $permissionKeys = Permission::whereIn('id', $validated['permissions'] ?? [])
            ->pluck('key')
            ->all();

        $hasCasePermissions = collect($permissionKeys)
            ->contains(fn (string $key) => str_starts_with($key, 'cases.'));

        if ($hasCasePermissions && $validated['case_scope'] === 'none') {
            return [null, false];
        }

        return [
            $permissionKeys,
            $request->boolean('requires_totp') || $hasCasePermissions,
        ];
    }
}

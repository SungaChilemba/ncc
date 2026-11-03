<?php

namespace App\Http\Controllers\Admin\Access;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CaseTotpMiddleware;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeView($request);

        $query = User::query()->with('roles');

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));

            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return view('admin.access.users.index', [
            'users' => $query->orderBy('name')->paginate(25)->withQueryString(),
            'canManage' => $request->user()->hasPermission('admin.users.manage'),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManage($request);

        return view('admin.access.users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => $this->availableRoles(),
            'canManage' => true,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('is_active', true)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        $roles = Role::whereIn('id', $validated['roles'])->get();
        $user->roles()->sync($roles->pluck('id'));

        AccessAudit::log(
            $request->user(),
            'user.created',
            $user,
            "Created user {$user->email}",
            ['roles' => $roles->pluck('key')->all()],
            $request,
        );

        return redirect()
            ->route('admin.access.users.index')
            ->with('status', "User {$user->email} created.");
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeView($request);

        return view('admin.access.users.form', [
            'user' => $user->load('roles'),
            'roles' => $this->availableRoles(),
            'canManage' => $request->user()->hasPermission('admin.users.manage'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('is_active', true)],
        ]);

        $selectedIds = array_map('intval', $validated['roles']);
        $currentIds = $user->roles()->pluck('roles.id')->map(fn ($id) => (int) $id)->all();

        if ($user->is($request->user()) && $this->setsDiffer($currentIds, $selectedIds)) {
            return back()->withErrors([
                'roles' => 'You cannot change your own role assignments. Ask another administrator.',
            ])->withInput();
        }

        $systemRole = Role::where('key', 'system_admin')->first();
        $losesSystemAdmin = $systemRole
            && in_array((int) $systemRole->id, $currentIds, true)
            && ! in_array((int) $systemRole->id, $selectedIds, true);

        if ($losesSystemAdmin && $this->activeSystemAdminCount($user->id) === 0) {
            return back()->withErrors([
                'roles' => 'At least one active system administrator must remain.',
            ])->withInput();
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        $roles = Role::whereIn('id', $selectedIds)->get();
        $user->roles()->sync($selectedIds);

        AccessAudit::log(
            $request->user(),
            'user.updated',
            $user,
            "Updated user {$user->email}",
            [
                'roles' => $roles->pluck('key')->all(),
                'password_changed' => filled($validated['password'] ?? null),
            ],
            $request,
        );

        return redirect()
            ->route('admin.access.users.index')
            ->with('status', "User {$user->email} updated.");
    }

    public function toggleActive(Request $request, User $user)
    {
        $this->authorizeManage($request);

        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        if ($user->is_active && $user->hasRole('system_admin') && $this->activeSystemAdminCount($user->id) === 0) {
            return back()->withErrors(['user' => 'At least one active system administrator must remain.']);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        if (! $user->is_active) {
            $user->forceFill(['remember_token' => null])->save();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        }

        AccessAudit::log(
            $request->user(),
            $user->is_active ? 'user.activated' : 'user.deactivated',
            $user,
            ($user->is_active ? 'Activated' : 'Deactivated')." user {$user->email}",
            [],
            $request,
        );

        return back()->with('status', $user->is_active
            ? "User {$user->email} activated."
            : "User {$user->email} deactivated and signed out.");
    }

    public function resetTwoFactor(Request $request, User $user)
    {
        $this->authorizeManage($request);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        if ($user->is($request->user())) {
            $request->session()->forget(CaseTotpMiddleware::SESSION_KEY);
        }

        AccessAudit::log(
            $request->user(),
            'user.two_factor_reset',
            $user,
            "Reset two-factor authentication for {$user->email}",
            [],
            $request,
        );

        return back()->with('status', "Two-factor authentication reset for {$user->email}.");
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyPermission('admin.users.view', 'admin.users.manage'),
            403,
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermission('admin.users.manage'), 403);
    }

    /**
     * @return Collection<int, Role>
     */
    private function availableRoles()
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    private function activeSystemAdminCount(?int $exceptUserId = null): int
    {
        return User::query()
            ->where('is_active', true)
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->whereHas('roles', fn ($query) => $query->where('key', 'system_admin'))
            ->count();
    }

    /**
     * @param  list<int>  $current
     * @param  list<int>  $selected
     */
    private function setsDiffer(array $current, array $selected): bool
    {
        sort($current);
        sort($selected);

        return $current !== array_values(array_unique($selected));
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'email',
    'password',
    'is_active',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const CASE_SCOPES = [
        'none' => 'No case access',
        'assigned' => 'Only assigned cases',
        'all' => 'All cases',
    ];

    /** @var list<string>|null */
    private ?array $permissionKeyCache = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->activeRoles()
            ->contains(fn (Role $role) => in_array($role->key, $roles, true));
    }

    /**
     * Check a permission key or wildcard pattern, for example
     * "cases.view", "cms.*", or "admin.*".
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->permissionKeys() as $key) {
            if (Str::is($permission, $key)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if ($this->permissionKeyCache !== null) {
            return $this->permissionKeyCache;
        }

        $this->loadMissing('roles.permissions');

        return $this->permissionKeyCache = $this->activeRoles()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('key'))
            ->unique()
            ->values()
            ->all();
    }

    public function caseScope(): string
    {
        $scopes = $this->activeRoles()->pluck('case_scope');

        return match (true) {
            $scopes->contains('all') => 'all',
            $scopes->contains('assigned') => 'assigned',
            default => 'none',
        };
    }

    public function caseScopeAllows(Report $report): bool
    {
        return match ($this->caseScope()) {
            'all' => true,
            'assigned' => (int) $report->assigned_to === (int) $this->id,
            default => false,
        };
    }

    public function hasCaseAccess(): bool
    {
        return $this->hasPermission('cases.*');
    }

    /**
     * Whether any of the user's active roles can be given case work.
     */
    public function canBeAssigned(): bool
    {
        return $this->activeRoles()->contains(
            fn (Role $role): bool => (bool) $role->can_be_assigned,
        );
    }

    public function canManageCms(): bool
    {
        return $this->hasPermission('cms.*');
    }

    public function canAccessAdmin(): bool
    {
        return $this->canManageCms()
            || $this->hasCaseAccess()
            || $this->hasPermission('admin.*');
    }

    public function canAccessAccessControl(): bool
    {
        return $this->hasAnyPermission(
            'admin.users.view',
            'admin.users.manage',
            'admin.roles.manage',
            'admin.audit.view',
        );
    }

    /**
     * Anyone with case permissions must use TOTP. Roles can also opt into TOTP
     * for administrative capabilities.
     */
    public function requiresTotp(): bool
    {
        return $this->hasCaseAccess()
            || $this->activeRoles()->contains(fn (Role $role) => $role->requires_totp);
    }

    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system_admin');
    }

    /**
     * @return Collection<int, Role>
     */
    public function caseRoles()
    {
        return $this->roles
            ->where('is_active', true)
            ->where('category', 'case')
            ->values();
    }

    public function caseRoleLabel(): ?string
    {
        return $this->caseRoles()->pluck('name')->join(', ') ?: null;
    }

    public function hasConfirmedTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && filled($this->two_factor_secret);
    }

    /**
     * @return Collection<int, Role>
     */
    private function activeRoles()
    {
        return $this->roles
            ->where('is_active', true)
            ->values();
    }
}

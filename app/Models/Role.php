<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'case_scope',
        'can_be_assigned',
        'requires_totp',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'can_be_assigned' => 'boolean',
        'requires_totp' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->where('can_be_assigned', true);
    }

    public function hasCasePermissions(): bool
    {
        return $this->permissions->contains(
            fn (Permission $permission) => str_starts_with($permission->key, 'cases.'),
        );
    }
}

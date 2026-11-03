<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ensure the code-owned permission catalog and built-in roles exist.
     */
    protected function seedAccessControl(): void
    {
        if (! Role::query()->where('key', 'system_admin')->exists()) {
            $this->seed(AccessControlSeeder::class);
        }
    }

    /**
     * Create a user and give them a single built-in role.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRole(string $roleKey, array $attributes = []): User
    {
        return $this->userWithRoles([$roleKey], $attributes);
    }

    /**
     * Create a user and give them several roles.
     *
     * @param  list<string>  $roleKeys
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRoles(array $roleKeys, array $attributes = []): User
    {
        $this->seedAccessControl();

        $user = User::factory()->create($attributes);
        $user->roles()->sync(
            Role::query()->whereIn('key', $roleKeys)->pluck('id'),
        );

        return $user->refresh();
    }
}

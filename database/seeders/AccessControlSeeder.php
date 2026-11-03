<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncPermissions();
        $this->syncRoles();
        $this->migrateLegacyUsers();
    }

    private function syncPermissions(): void
    {
        $catalog = AccessCatalog::permissions();

        foreach ($catalog as $key => $definition) {
            Permission::updateOrCreate(
                ['key' => $key],
                [
                    'group' => $definition['group'],
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'is_dangerous' => $definition['dangerous'],
                ],
            );
        }

        Permission::whereNotIn('key', array_keys($catalog))->delete();
    }

    private function syncRoles(): void
    {
        foreach (AccessCatalog::roles() as $key => $definition) {
            $role = Role::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'category' => $definition['category'],
                    'case_scope' => $definition['case_scope'],
                    'can_be_assigned' => $definition['can_be_assigned'],
                    'requires_totp' => $definition['requires_totp'],
                    'is_system' => $definition['is_system'],
                    'is_active' => true,
                ],
            );

            $role->permissions()->sync(
                Permission::whereIn('key', $definition['permissions'])->pluck('id'),
            );
        }
    }

    /**
     * Convert the pre-RBAC role columns into pivot rows. These columns are no
     * longer read by authorisation and can be dropped in a later release.
     */
    private function migrateLegacyUsers(): void
    {
        if (! Schema::hasColumn('users', 'case_role')) {
            return;
        }

        User::query()->whereDoesntHave('roles')->each(function (User $user) {
            $roleKeys = [];

            if ((bool) $user->getAttribute('is_admin')) {
                $roleKeys[] = 'system_admin';
            } else {
                $legacyRole = (string) $user->getAttribute('role');

                if ($legacyRole === 'admin') {
                    $roleKeys[] = 'cms_admin';
                } elseif ($legacyRole === 'editor') {
                    $roleKeys[] = 'cms_editor';
                }
            }

            $legacyCaseRole = (string) $user->getAttribute('case_role');

            if (in_array($legacyCaseRole, ['intake', 'officer', 'manager', 'auditor'], true)) {
                $roleKeys[] = 'case_'.$legacyCaseRole;
            }

            if ($roleKeys === []) {
                return;
            }

            $ids = Role::whereIn('key', array_unique($roleKeys))->pluck('id');

            if ($ids->isNotEmpty()) {
                $user->roles()->sync($ids);
            }
        });
    }
}

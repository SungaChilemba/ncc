<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_create_a_role_with_permissions_and_the_change_is_audited(): void
    {
        $admin = $this->userWithRole('system_admin');
        $permission = Permission::where('key', 'admin.users.view')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.access.roles.store'), [
                'name' => 'Support Lead',
                'description' => 'Reviews admin accounts and helps staff sign in.',
                'category' => 'custom',
                'case_scope' => 'none',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect(route('admin.access.roles.index'));

        $role = Role::where('key', 'support_lead')->firstOrFail();

        $this->assertFalse($role->is_system);
        $this->assertTrue($role->permissions()->whereKey($permission->id)->exists());

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'role.created',
            'target_id' => $role->id,
        ]);
    }

    public function test_custom_role_permissions_take_effect_without_code_changes(): void
    {
        $admin = $this->userWithRole('system_admin');
        $permission = Permission::where('key', 'admin.users.view')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.access.roles.store'), [
                'name' => 'User Reviewer',
                'category' => 'custom',
                'case_scope' => 'none',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect();

        $role = Role::where('key', 'user_reviewer')->firstOrFail();
        $reviewer = $this->userWithRole($role->key);

        $this->actingAs($reviewer)
            ->get(route('admin.access.users.index'))
            ->assertOk()
            ->assertSee('Users & Access')
            ->assertDontSee('New user');

        $this->actingAs($reviewer)
            ->get(route('admin.access.roles.index'))
            ->assertForbidden();
    }

    public function test_case_permissions_require_a_case_scope_and_force_two_factor(): void
    {
        $admin = $this->userWithRole('system_admin');
        $viewCases = Permission::where('key', 'cases.view')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.access.roles.create'))
            ->post(route('admin.access.roles.store'), [
                'name' => 'Case Reader',
                'category' => 'case',
                'case_scope' => 'none',
                'permissions' => [$viewCases->id],
            ])
            ->assertRedirect(route('admin.access.roles.create'))
            ->assertSessionHasErrors('case_scope');

        $this->assertDatabaseMissing('roles', ['key' => 'case_reader']);

        $this->actingAs($admin)
            ->post(route('admin.access.roles.store'), [
                'name' => 'Case Reader',
                'category' => 'case',
                'case_scope' => 'all',
                'permissions' => [$viewCases->id],
            ])
            ->assertRedirect(route('admin.access.roles.index'));

        $role = Role::where('key', 'case_reader')->firstOrFail();

        $this->assertTrue($role->requires_totp);
        $this->assertSame('all', $role->case_scope);
    }

    public function test_system_admin_can_create_a_user_and_assign_multiple_roles(): void
    {
        $admin = $this->userWithRole('system_admin');
        $cmsEditor = Role::where('key', 'cms_editor')->firstOrFail();
        $caseAuditor = Role::where('key', 'case_auditor')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.access.users.store'), [
                'name' => 'Hybrid Staff',
                'email' => 'hybrid@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => [$cmsEditor->id, $caseAuditor->id],
            ])
            ->assertRedirect(route('admin.access.users.index'));

        $user = User::where('email', 'hybrid@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('cms_editor'));
        $this->assertTrue($user->hasRole('case_auditor'));
        $this->assertTrue($user->canManageCms());
        $this->assertTrue($user->hasCaseAccess());
        $this->assertTrue($user->requiresTotp());

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'user.created',
            'target_id' => $user->id,
        ]);
    }

    public function test_an_administrator_cannot_change_their_own_roles(): void
    {
        $admin = $this->userWithRole('system_admin');
        $editor = Role::where('key', 'cms_editor')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.access.users.edit', $admin))
            ->put(route('admin.access.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'roles' => [$editor->id],
            ])
            ->assertRedirect(route('admin.access.users.edit', $admin))
            ->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole('system_admin'));
    }

    public function test_the_last_active_system_administrator_cannot_be_deactivated(): void
    {
        $admin = $this->userWithRole('system_admin');

        $userManagerRole = Role::create([
            'key' => 'user_manager',
            'name' => 'User Manager',
            'description' => 'Manages accounts without being a system administrator.',
            'category' => 'admin',
            'case_scope' => 'none',
            'can_be_assigned' => false,
            'requires_totp' => false,
            'is_system' => false,
            'is_active' => true,
        ]);
        $userManagerRole->permissions()->sync(
            Permission::whereIn('key', ['admin.users.view', 'admin.users.manage'])->pluck('id'),
        );

        $userManager = $this->userWithRole('user_manager');

        $this->actingAs($userManager)
            ->from(route('admin.access.users.index'))
            ->post(route('admin.access.users.toggle-active', $admin))
            ->assertRedirect(route('admin.access.users.index'))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_built_in_roles_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('system_admin');
        $role = Role::where('key', 'case_officer')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.access.roles.index'))
            ->delete(route('admin.access.roles.destroy', $role))
            ->assertRedirect(route('admin.access.roles.index'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['key' => 'case_officer']);
    }

    public function test_access_audit_page_lists_recent_changes(): void
    {
        $admin = $this->userWithRole('system_admin');
        $permission = Permission::where('key', 'admin.audit.view')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.access.roles.store'), [
                'name' => 'Audit Reader',
                'category' => 'custom',
                'case_scope' => 'none',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect();

        AdminAuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'user.two_factor_reset',
            'summary' => 'Reset two-factor authentication for someone@example.com',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.access.audit'))
            ->assertOk()
            ->assertSee('role.created')
            ->assertSee('user.two_factor_reset')
            ->assertSee('Reset two-factor authentication for someone@example.com');
    }

    public function test_access_management_pages_render_for_a_system_admin(): void
    {
        $admin = $this->userWithRole('system_admin');
        $systemRole = Role::where('key', 'system_admin')->firstOrFail();

        $this->actingAs($admin);

        $this->get(route('admin.access.users.index'))
            ->assertOk()
            ->assertSee('Users & Access');

        $this->get(route('admin.access.users.create'))
            ->assertOk()
            ->assertSee('Create user');

        $this->get(route('admin.access.roles.index'))
            ->assertOk()
            ->assertSee('Built-in');

        $this->get(route('admin.access.roles.create'))
            ->assertOk()
            ->assertSee('Case data scope');

        $this->get(route('admin.access.roles.edit', $systemRole))
            ->assertOk()
            ->assertSee('protected system administrator role');

        $this->get(route('admin.access.audit'))->assertOk();
    }

    public function test_the_protected_system_admin_role_keeps_its_fixed_permissions(): void
    {
        $admin = $this->userWithRole('system_admin');
        $role = Role::where('key', 'system_admin')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.access.roles.update', $role), [
                'name' => 'System Administrator',
                'description' => 'Updated description.',
            ])
            ->assertRedirect(route('admin.access.roles.index'));

        $role->refresh();

        $this->assertSame('none', $role->case_scope);
        $this->assertFalse($role->requires_totp);
        $this->assertFalse($role->can_be_assigned);
        $this->assertTrue($role->permissions()->where('key', 'admin.roles.manage')->exists());
        $this->assertTrue($role->permissions()->where('key', 'cms.content.publish')->exists());
    }

    public function test_users_without_administration_permissions_cannot_manage_access(): void
    {
        $cmsEditor = $this->userWithRole('cms_editor');

        $this->actingAs($cmsEditor)
            ->get(route('admin.access.users.index'))
            ->assertForbidden();

        $this->actingAs($cmsEditor)
            ->get(route('admin.access.roles.index'))
            ->assertForbidden();

        $this->actingAs($cmsEditor)
            ->get(route('admin.access.audit'))
            ->assertForbidden();
    }
}

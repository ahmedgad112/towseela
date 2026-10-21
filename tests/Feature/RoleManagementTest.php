<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Support\AuditLogger;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    public function test_guest_is_redirected_from_roles_page(): void
    {
        $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
    }

    public function test_admin_is_forbidden_from_roles_page(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_roles_page(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_super_admin_can_create_a_custom_role(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'shift_lead',
                'label' => 'مشرف وردية',
                'permissions' => ['orders.view', 'orders.confirm'],
            ])
            ->assertRedirect();

        $role = Role::query()->where('name', 'shift_lead')->first();

        $this->assertNotNull($role);
        $this->assertSame('مشرف وردية', $role->label);
        $this->assertFalse((bool) $role->is_system);
        $this->assertTrue($role->hasPermissionTo('orders.confirm'));
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::RoleCreated->value)->exists());
    }

    public function test_super_admin_can_update_role_permissions(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $role = Role::findByName('dispatcher', 'web');

        $this->actingAs($superAdmin)
            ->patch(route('admin.roles.update', $role), [
                'permissions' => ['orders.view', 'orders.assign_driver'],
            ])
            ->assertRedirect();

        $this->assertTrue($role->fresh()->hasPermissionTo('orders.assign_driver'));
        $this->assertFalse($role->fresh()->hasPermissionTo('orders.update'));
    }

    public function test_super_admin_role_cannot_be_updated_or_deleted(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $role = Role::findByName('super_admin', 'web');

        $this->actingAs($superAdmin)
            ->patch(route('admin.roles.update', $role), [
                'permissions' => ['dashboard.view'],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue(Role::query()->where('name', 'super_admin')->exists());
        $this->assertTrue($role->fresh()->hasPermissionTo('roles.delete'));
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $role = Role::findByName('admin', 'web');

        $this->actingAs($superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue(Role::query()->where('name', 'admin')->exists());
    }

    public function test_custom_role_with_users_cannot_be_deleted(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $role = Role::query()->create([
            'name' => 'night_shift',
            'guard_name' => 'web',
            'label' => 'وردية ليلية',
            'is_system' => false,
        ]);
        $this->userWithRole('admin')->assignRole($role);

        $this->actingAs($superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue(Role::query()->where('name', 'night_shift')->exists());
    }

    public function test_super_admin_can_delete_unused_custom_role(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $role = Role::query()->create([
            'name' => 'temp_auditor',
            'guard_name' => 'web',
            'label' => 'مدقق مؤقت',
            'is_system' => false,
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse(Role::query()->where('name', 'temp_auditor')->exists());
    }

    public function test_super_admin_can_create_and_delete_custom_permission(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.permissions.store'), [
                'name' => 'orders.print',
                'label' => 'طباعة الطلب',
                'group' => 'الطلبات',
            ])
            ->assertRedirect();

        $permission = Permission::query()->where('name', 'orders.print')->first();

        $this->assertNotNull($permission);
        $this->assertFalse((bool) $permission->is_system);

        $this->actingAs($superAdmin)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse(Permission::query()->where('name', 'orders.print')->exists());
    }

    public function test_system_permission_cannot_be_deleted(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $permission = Permission::findByName('orders.view', 'web');

        $this->actingAs($superAdmin)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue(Permission::query()->where('name', 'orders.view')->exists());
    }

    public function test_admin_can_view_audit_logs_and_agent_cannot(): void
    {
        $admin = $this->userWithRole('admin');
        $agent = $this->userWithRole('call_center_agent');

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk();

        $this->actingAs($agent)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_audit_logs_can_be_filtered_by_action(): void
    {
        $superAdmin = $this->userWithRole('super_admin');
        $role = Role::findByName('dispatcher', 'web');

        app(AuditLogger::class)->record(AuditAction::RoleUpdated, $role, $superAdmin, ['name' => $role->name]);
        app(AuditLogger::class)->record(AuditAction::SettingsUpdated, $superAdmin, $superAdmin);

        $this->actingAs($superAdmin)
            ->get(route('admin.audit-logs.index', ['action' => AuditAction::RoleUpdated->value]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLogs/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.action', AuditAction::RoleUpdated->value));
    }

    public function test_role_store_validation_rejects_invalid_name(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'مشرف',
                'label' => 'مشرف',
            ])
            ->assertSessionHasErrors('name');
    }
}

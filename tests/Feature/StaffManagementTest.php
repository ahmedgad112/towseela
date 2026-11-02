<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    public function test_guest_is_redirected_from_the_staff_page(): void
    {
        $this->get(route('admin.staff.index'))->assertRedirect(route('login'));
    }

    #[DataProvider('rolesForbiddenFromStaffPage')]
    public function test_roles_without_staff_access_are_forbidden(string $role): void
    {
        $user = $this->userWithRole($role);

        $this->actingAs($user)
            ->get(route('admin.staff.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rolesForbiddenFromStaffPage(): array
    {
        return [
            'customer' => ['customer'],
            'driver' => ['driver'],
            'call_center_agent' => ['call_center_agent'],
            'dispatcher' => ['dispatcher'],
            'business_manager' => ['business_manager'],
        ];
    }

    public function test_admin_can_view_the_staff_page(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Staff/Index')
                ->where('can.create', true)
                ->where('can.update', true)
                ->where('can.delete', true)
                ->has('staff.data')
                ->has('roles'));
    }

    public function test_admin_can_create_a_staff_member(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [
                'name' => 'موظف جديد',
                'email' => 'new-staff@towseela.test',
                'phone' => '01012345678',
                'password' => 'Password1!',
                'role' => 'dispatcher',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $staff = User::query()->where('email', 'new-staff@towseela.test')->first();

        $this->assertNotNull($staff);
        $this->assertTrue($staff->hasRole('dispatcher'));
        $this->assertTrue($staff->is_active);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::UserCreated->value)->exists());
    }

    public function test_admin_cannot_assign_the_super_admin_role(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [
                'name' => 'ترقية غير مسموحة',
                'email' => 'blocked@towseela.test',
                'phone' => '01012345679',
                'password' => 'Password1!',
                'role' => 'super_admin',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'blocked@towseela.test']);
    }

    public function test_staff_store_validation_rejects_an_empty_payload(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors(['name', 'email', 'phone', 'password', 'role']);
    }

    public function test_admin_can_update_a_staff_member_role_and_status(): void
    {
        $admin = $this->userWithRole('admin');
        $staff = User::factory()->create();
        $staff->assignRole('call_center_agent');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->patch(route('admin.staff.update', $staff), [
                'name' => 'وكيل محدث',
                'email' => $staff->email,
                'phone' => $staff->phone,
                'role' => 'dispatcher',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $staff->refresh();

        $this->assertSame('وكيل محدث', $staff->name);
        $this->assertTrue($staff->hasRole('dispatcher'));
        $this->assertFalse($staff->hasRole('call_center_agent'));
        $this->assertFalse($staff->is_active);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::UserUpdated->value)->exists());
    }

    public function test_admin_can_reset_a_staff_password(): void
    {
        $admin = $this->userWithRole('admin');
        $staff = User::factory()->create();
        $staff->assignRole('dispatcher');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->patch(route('admin.staff.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'password' => 'ResetPass1!',
                'role' => 'dispatcher',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.staff.index'));

        $this->assertTrue(Hash::check('ResetPass1!', $staff->fresh()->password));
    }

    public function test_admin_cannot_update_a_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->patch(route('admin.staff.update', $superAdmin), [
                'name' => 'محاولة تعديل',
                'email' => $superAdmin->email,
                'phone' => $superAdmin->phone,
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertForbidden();

        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->patch(route('admin.staff.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => 'dispatcher',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertFalse($admin->fresh()->hasRole('dispatcher'));
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->patch(route('admin.staff.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_delete_a_staff_member(): void
    {
        $admin = $this->userWithRole('admin');
        $staff = User::factory()->create([
            'email' => 'delete-me@towseela.test',
        ]);
        $staff->assignRole('dispatcher');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->delete(route('admin.staff.destroy', $staff))
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['email' => 'delete-me@towseela.test']);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::UserDeleted->value)->exists());
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->from(route('admin.staff.index'))
            ->delete(route('admin.staff.destroy', $admin))
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->from(route('admin.staff.index'))
            ->delete(route('admin.staff.destroy', $superAdmin))
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($superAdmin);
    }

    public function test_admin_cannot_delete_a_super_admin(): void
    {
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($admin)
            ->delete(route('admin.staff.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertModelExists($superAdmin);
    }

    public function test_staff_destroy_returns_404_for_a_customer(): void
    {
        $admin = $this->userWithRole('admin');
        $customer = $this->userWithRole('customer');

        $this->actingAs($admin)
            ->delete(route('admin.staff.destroy', $customer))
            ->assertNotFound();

        $this->assertModelExists($customer);
    }

    public function test_agent_cannot_create_staff(): void
    {
        $agent = $this->userWithRole('call_center_agent');

        $this->actingAs($agent)
            ->post(route('admin.staff.store'), [
                'name' => 'موظف',
                'email' => 'agent-create@towseela.test',
                'phone' => '01012345670',
                'password' => 'password',
                'role' => 'dispatcher',
            ])
            ->assertForbidden();
    }
}

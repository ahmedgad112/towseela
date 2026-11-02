<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function test_admin_can_view_create_update_and_delete_staff(): void
    {
        $policy = new UserPolicy;
        $admin = $this->userWithRole('admin');
        $agent = $this->userWithRole('call_center_agent');

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $agent));
        $this->assertTrue($policy->delete($admin, $agent));
    }

    public function test_admin_cannot_update_or_delete_a_super_admin(): void
    {
        $policy = new UserPolicy;
        $admin = $this->userWithRole('admin');
        $superAdmin = $this->userWithRole('super_admin');

        $this->assertFalse($policy->update($admin, $superAdmin));
        $this->assertFalse($policy->delete($admin, $superAdmin));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $policy = new UserPolicy;
        $admin = $this->userWithRole('admin');

        $this->assertFalse($policy->delete($admin, $admin));
    }

    #[DataProvider('rolesWithoutUserManagement')]
    public function test_roles_without_user_management_cannot_change_staff(string $role): void
    {
        $policy = new UserPolicy;
        $actor = $this->userWithRole($role);
        $target = User::factory()->create();
        $target->assignRole('admin');

        $this->assertFalse($policy->create($actor));
        $this->assertFalse($policy->update($actor, $target));
        $this->assertFalse($policy->delete($actor, $target));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function rolesWithoutUserManagement(): array
    {
        return [
            'call_center_agent' => ['call_center_agent'],
            'dispatcher' => ['dispatcher'],
            'business_manager' => ['business_manager'],
            'driver' => ['driver'],
            'customer' => ['customer'],
        ];
    }

    public function test_call_center_agent_can_view_users_but_not_manage_them(): void
    {
        $policy = new UserPolicy;
        $agent = $this->userWithRole('call_center_agent');
        $dispatcher = $this->userWithRole('dispatcher');

        $this->assertTrue($policy->viewAny($agent));
        $this->assertTrue($policy->view($agent, $dispatcher));
        $this->assertFalse($policy->create($agent));
        $this->assertFalse($policy->update($agent, $dispatcher));
        $this->assertFalse($policy->delete($agent, $dispatcher));
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_guest_is_redirected_from_the_profile_page(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_customer_can_open_account_and_profile_pages(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)
            ->get(route('customer.profile'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Profile')
                ->where('user.id', $customer->id)
                ->where('user.email', $customer->email));

        $this->actingAs($customer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Profile')
                ->where('user.id', $customer->id));
    }

    #[DataProvider('workspaceProfiles')]
    public function test_each_workspace_opens_its_profile_page(string $role, string $component): void
    {
        $user = $this->userWithRole($role);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function workspaceProfiles(): array
    {
        return [
            'admin' => ['admin', 'Admin/Profile'],
            'super_admin' => ['super_admin', 'Admin/Profile'],
            'business_manager' => ['business_manager', 'Admin/Profile'],
            'call_center_agent' => ['call_center_agent', 'CallCenter/Profile'],
            'dispatcher' => ['dispatcher', 'CallCenter/Profile'],
            'driver' => ['driver', 'Driver/Profile'],
        ];
    }

    public function test_user_can_update_their_profile(): void
    {
        $user = $this->userWithRole('admin');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'مدير محدث',
                'email' => 'updated-admin@towseela.test',
                'phone' => '01011112222',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('مدير محدث', $user->name);
        $this->assertSame('updated-admin@towseela.test', $user->email);
        $this->assertSame('01011112222', $user->phone);
    }

    public function test_profile_update_rejects_a_duplicate_email(): void
    {
        $user = $this->userWithRole('admin');
        $other = $this->userWithRole('customer');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $other->email,
                'phone' => $user->phone,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_change_their_password(): void
    {
        $user = $this->userWithRole('customer');

        $this->actingAs($user)
            ->from(route('customer.profile'))
            ->put(route('customer.profile.password'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = $this->userWithRole('admin');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_rejects_an_unconfirmed_password(): void
    {
        $user = $this->userWithRole('driver');

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'mismatch',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('password');
    }

    public function test_staff_cannot_change_their_role_through_the_profile(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->patch(route('profile.update'), [
                'name' => 'محاولة ترقية',
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => 'super_admin',
                'is_active' => false,
            ])
            ->assertRedirect();

        $admin->refresh();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($admin->hasRole('super_admin'));
        $this->assertTrue($admin->is_active);
        $this->assertSame('محاولة ترقية', $admin->name);
    }
}

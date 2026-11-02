<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_guest_is_redirected_from_protected_customer_routes(): void
    {
        $this->get('/orders')->assertRedirect(route('login'));
    }

    public function test_customer_can_register_and_reach_home(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'عميل جديد',
            'email' => 'new@towseela.test',
            'phone' => '01099999999',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertRedirect(route('customer.home'));

        $this->assertAuthenticated();
        $this->assertTrue(User::query()->where('email', 'new@towseela.test')->first()?->hasRole('customer'));
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $this->seedRoles();

        $this->from(route('register'))
            ->post('/register', [
                'name' => 'عميل ضعيف',
                'email' => 'weak@towseela.test',
                'phone' => '01088888888',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'weak@towseela.test']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->seedRoles();

        $user = User::factory()->create([
            'email' => 'off@towseela.test',
            'is_active' => false,
        ]);
        $user->assignRole('customer');

        $this->post('/login', [
            'login' => 'off@towseela.test',
            'password' => 'password',
        ])->assertSessionHasErrors('login');
    }

    public function test_driver_is_sent_to_driver_portal_after_login(): void
    {
        $driver = $this->userWithRole('driver');

        $this->post('/login', [
            'login' => $driver->email,
            'password' => 'password',
        ])->assertRedirect(route('driver.dashboard'));
    }

    public function test_business_manager_is_sent_to_admin_portal_after_login(): void
    {
        $manager = $this->userWithRole('business_manager');

        $this->post('/login', [
            'login' => $manager->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_call_center_agent_is_sent_to_dispatch_after_login(): void
    {
        $agent = $this->userWithRole('call_center_agent');

        $this->post('/login', [
            'login' => $agent->email,
            'password' => 'password',
        ])->assertRedirect(route('call-center.dashboard'));
    }
}

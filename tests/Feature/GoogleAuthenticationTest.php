<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => 'http://towseela.test/auth/google/callback',
        ]);
    }

    public function test_guest_is_redirected_to_google(): void
    {
        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))->assertRedirect();
    }

    public function test_google_auth_is_unavailable_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('auth.google.redirect'))->assertStatus(503);
    }

    public function test_new_customer_can_register_with_google(): void
    {
        $this->seedRoles();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-new-1',
            'name' => 'عميل جوجل',
            'email' => 'google-new@towseela.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('customer.home'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'google-new@towseela.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('google-new-1', $user->google_id);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
    }

    public function test_existing_user_can_login_with_google_and_link_account(): void
    {
        $this->seedRoles();

        $user = User::factory()->create([
            'email' => 'linked@towseela.test',
            'google_id' => null,
        ]);
        $user->assignRole('customer');

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-linked-1',
            'name' => $user->name,
            'email' => 'linked@towseela.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('customer.home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-linked-1', $user->fresh()->google_id);
    }

    public function test_existing_google_user_can_login_again(): void
    {
        $this->seedRoles();

        $user = User::factory()->create([
            'email' => 'returning@towseela.test',
            'google_id' => 'google-returning-1',
        ]);
        $user->assignRole('customer');

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-returning-1',
            'name' => $user->name,
            'email' => 'returning@towseela.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('customer.home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'returning@towseela.test')->count());
    }

    public function test_inactive_google_user_cannot_login(): void
    {
        $this->seedRoles();

        $user = User::factory()->create([
            'email' => 'inactive-google@towseela.test',
            'google_id' => 'google-inactive-1',
            'is_active' => false,
        ]);
        $user->assignRole('customer');

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-inactive-1',
            'name' => $user->name,
            'email' => 'inactive-google@towseela.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_google_registration_is_blocked_when_registration_disabled(): void
    {
        $this->seedRoles();

        PlatformSetting::setValue('features', [
            'customer_registration_enabled' => false,
            'maintenance_mode' => false,
            'maintenance_message' => '',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-blocked-1',
            'name' => 'محظور',
            'email' => 'blocked-google@towseela.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'blocked-google@towseela.test']);
    }

    public function test_driver_with_google_is_sent_to_driver_portal(): void
    {
        $driver = $this->userWithRole('driver');
        $driver->forceFill(['google_id' => 'google-driver-1'])->save();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-driver-1',
            'name' => $driver->name,
            'email' => $driver->email,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('driver.dashboard'));

        $this->assertAuthenticatedAs($driver);
    }

    public function test_admin_can_login_with_google_using_matching_email(): void
    {
        $admin = $this->userWithRole('admin');
        $admin->forceFill(['google_id' => null])->save();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-admin-1',
            'name' => $admin->name,
            'email' => $admin->email,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('google-admin-1', $admin->fresh()->google_id);
    }

    public function test_authenticated_user_can_link_google_from_profile(): void
    {
        $customer = $this->userWithRole('customer');
        $customer->forceFill(['google_id' => null])->save();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-link-customer-1',
            'name' => $customer->name,
            'email' => $customer->email,
        ]));

        $this->actingAs($customer)
            ->withSession(['google_auth_intent' => 'link'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHas('success');

        $this->assertSame('google-link-customer-1', $customer->fresh()->google_id);
    }

    public function test_authenticated_admin_can_link_google_from_profile(): void
    {
        $admin = $this->userWithRole('admin');
        $admin->forceFill(['google_id' => null])->save();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-link-admin-1',
            'name' => $admin->name,
            'email' => $admin->email,
        ]));

        $this->actingAs($admin)
            ->withSession(['google_auth_intent' => 'link'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $this->assertSame('google-link-admin-1', $admin->fresh()->google_id);
    }

    public function test_google_link_rejects_mismatched_email(): void
    {
        $user = $this->userWithRole('customer');
        $user->forceFill(['google_id' => null])->save();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-mismatch-1',
            'name' => $user->name,
            'email' => 'other-google@towseela.test',
        ]));

        $this->actingAs($user)
            ->withSession(['google_auth_intent' => 'link'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHas('error');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_user_can_unlink_google_when_password_exists(): void
    {
        $user = $this->userWithRole('admin');
        $user->forceFill(['google_id' => 'google-unlink-1'])->save();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('auth.google.unlink'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_google_only_user_cannot_unlink_without_password(): void
    {
        $this->seedRoles();

        $user = User::factory()->create([
            'email' => 'google-only@towseela.test',
            'password' => null,
            'google_id' => 'google-only-1',
        ]);
        $user->assignRole('customer');

        $this->actingAs($user)
            ->from(route('customer.profile'))
            ->delete(route('auth.google.unlink'))
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHas('error');

        $this->assertSame('google-only-1', $user->fresh()->google_id);
    }

    public function test_authenticated_redirect_sets_link_intent(): void
    {
        Socialite::fake('google');

        $user = $this->userWithRole('customer');

        $this->actingAs($user)
            ->get(route('auth.google.redirect'))
            ->assertRedirect()
            ->assertSessionHas('google_auth_intent', 'link');
    }
}

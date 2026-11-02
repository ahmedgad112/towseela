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
}

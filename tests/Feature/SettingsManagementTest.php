<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformSetting;
use App\Support\PlatformSettings;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    public function test_guest_is_redirected_from_settings_page(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    }

    public function test_admin_without_settings_permission_is_forbidden(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_settings_page(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->has('settings.brand')
                ->has('settings.operations')
                ->has('settings.map')
                ->has('settings.features'));
    }

    public function test_super_admin_can_update_platform_settings(): void
    {
        $superAdmin = $this->userWithRole('super_admin');

        $this->actingAs($superAdmin)
            ->from(route('admin.settings.edit'))
            ->patch(route('admin.settings.update'), [
                'brand' => [
                    'name_ar' => 'منصة جديدة',
                    'name_en' => 'New Platform',
                    'tagline' => 'توصيل أسرع',
                    'support_phone' => '19888',
                    'support_email' => 'help@towseela.test',
                    'whatsapp' => '01011111111',
                ],
                'operations' => [
                    'currency' => 'EGP',
                    'currency_label' => 'جنيه',
                    'allow_mixed_business_cart' => true,
                    'require_proof_of_delivery_note' => true,
                    'driver_active_order_limit' => 5,
                    'overdue_minutes' => 120,
                ],
                'map' => [
                    'default_lat' => 31.2,
                    'default_lng' => 29.9,
                    'default_city' => 'الإسكندرية',
                ],
                'features' => [
                    'customer_registration_enabled' => false,
                    'maintenance_mode' => true,
                    'maintenance_message' => 'نعمل على تحسين الخدمة',
                ],
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('success');

        $settings = app(PlatformSettings::class)->all();

        $this->assertSame('منصة جديدة', $settings['brand']['name_ar']);
        $this->assertSame('جنيه', $settings['operations']['currency_label']);
        $this->assertTrue($settings['operations']['require_proof_of_delivery_note']);
        $this->assertSame(5, $settings['operations']['driver_active_order_limit']);
        $this->assertSame('الإسكندرية', $settings['map']['default_city']);
        $this->assertFalse($settings['features']['customer_registration_enabled']);
        $this->assertTrue($settings['features']['maintenance_mode']);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::SettingsUpdated->value)->exists());
    }

    public function test_registration_is_blocked_when_disabled_in_settings(): void
    {
        $this->seedRoles();

        PlatformSetting::setValue('features', [
            'customer_registration_enabled' => false,
            'maintenance_mode' => false,
            'maintenance_message' => '',
        ]);

        $this->get(route('register'))->assertForbidden();

        $this->post(route('register'), [
            'name' => 'عميل',
            'email' => 'blocked-register@towseela.test',
            'phone' => '01022223333',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
    }

    public function test_shared_inertia_props_use_platform_settings(): void
    {
        $this->seedRoles();

        PlatformSetting::setValue('brand', [
            'name_ar' => 'براند تجريبي',
            'name_en' => 'Trial Brand',
            'tagline' => 'شعار تجريبي',
            'support_phone' => '19000',
            'support_email' => '',
            'whatsapp' => '',
        ]);

        PlatformSetting::setValue('operations', [
            'currency' => 'EGP',
            'currency_label' => 'جنيه مصري',
            'allow_mixed_business_cart' => false,
            'require_proof_of_delivery_note' => false,
            'driver_active_order_limit' => 3,
            'overdue_minutes' => 90,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('appName', 'براند تجريبي')
                ->where('currencyLabel', 'جنيه مصري')
                ->where('platform.brand.name_ar', 'براند تجريبي'));
    }
}

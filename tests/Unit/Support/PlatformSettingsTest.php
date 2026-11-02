<?php

namespace Tests\Unit\Support;

use App\Models\PlatformSetting;
use App\Support\PlatformSettings;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    public function test_it_falls_back_to_config_defaults_when_nothing_is_stored(): void
    {
        $settings = app(PlatformSettings::class)->operations();

        $this->assertSame(config('towseela.currency_label'), $settings['currency_label']);
        $this->assertSame((int) config('towseela.driver_active_order_limit'), $settings['driver_active_order_limit']);
    }

    public function test_it_prefers_stored_platform_settings_over_config(): void
    {
        PlatformSetting::setValue('operations', [
            'currency' => 'EGP',
            'currency_label' => 'جنيه محفوظ',
            'allow_mixed_business_cart' => true,
            'require_proof_of_delivery_note' => true,
            'driver_active_order_limit' => 7,
            'overdue_minutes' => 45,
        ]);

        $settings = app(PlatformSettings::class)->operations();

        $this->assertSame('جنيه محفوظ', $settings['currency_label']);
        $this->assertTrue($settings['allow_mixed_business_cart']);
        $this->assertSame(7, $settings['driver_active_order_limit']);
        $this->assertSame(45, $settings['overdue_minutes']);
    }
}

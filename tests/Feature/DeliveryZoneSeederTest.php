<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use Database\Seeders\DeliveryZoneSeeder;
use Tests\TestCase;

class DeliveryZoneSeederTest extends TestCase
{
    public function test_seeder_creates_zones_for_every_configured_governorate(): void
    {
        $this->seed(DeliveryZoneSeeder::class);

        $governorates = collect(config('towseela.governorates'))->pluck('name');

        foreach ($governorates as $governorate) {
            $this->assertTrue(
                DeliveryZone::query()->where('governorate', $governorate)->exists(),
                "Expected delivery zones for {$governorate}",
            );
        }

        $this->assertGreaterThan(100, DeliveryZone::query()->count());
        $this->assertTrue(
            DeliveryZone::query()->where('name_ar', 'المعادي')->where('governorate', 'القاهرة')->exists(),
        );
        $this->assertDatabaseHas('delivery_fee_rules', [
            'min_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DeliveryZoneSeeder::class);
        $count = DeliveryZone::query()->count();

        $this->seed(DeliveryZoneSeeder::class);

        $this->assertSame($count, DeliveryZone::query()->count());
    }
}

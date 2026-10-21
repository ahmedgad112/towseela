<?php

namespace Database\Factories;

use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryFeeRule>
 */
class DeliveryFeeRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_zone_id' => DeliveryZone::factory(),
            'min_order' => 0,
            'fee' => 15,
            'is_active' => true,
        ];
    }
}

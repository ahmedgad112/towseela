<?php

namespace Database\Factories;

use App\Enums\AddressLabel;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => AddressLabel::Home,
            'line1' => fake()->streetAddress(),
            'city' => 'القاهرة',
            'delivery_zone_id' => DeliveryZone::factory(),
            'is_default' => true,
        ];
    }
}

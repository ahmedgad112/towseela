<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'business_category_id' => BusinessCategory::factory(),
            'name_ar' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'is_active' => true,
            'accepts_orders' => true,
            'min_order_amount' => 0,
            'delivery_time_min' => 25,
            'delivery_time_max' => 45,
        ];
    }
}

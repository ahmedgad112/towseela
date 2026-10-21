<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'product_category_id' => fn (array $attributes) => ProductCategory::factory()->create([
                'business_id' => $attributes['business_id'],
            ])->id,
            'name_ar' => fake()->words(3, true),
            'price' => 50,
            'is_available' => true,
            'is_active' => true,
        ];
    }
}

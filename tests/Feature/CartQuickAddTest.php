<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class CartQuickAddTest extends TestCase
{
    /**
     * @return array{0: User, 1: Product}
     */
    private function customerWithProduct(): array
    {
        $this->seedRoles();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $business = Business::factory()->create();
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'price' => 40,
        ]);

        return [$customer, $product];
    }

    public function test_customer_can_add_product_to_cart_in_one_step(): void
    {
        [$customer, $product] = $this->customerWithProduct();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_json_add_returns_cart_snapshot_without_redirect(): void
    {
        [$customer, $product] = $this->customerWithProduct();

        $response = $this->actingAs($customer)
            ->postJson('/cart', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.product_id', $product->id)
            ->assertJsonPath('items.0.quantity', 1);

        $this->assertArrayNotHasKey('subtotal', $response->json());
    }

    public function test_json_quantity_update_returns_updated_snapshot(): void
    {
        [$customer, $product] = $this->customerWithProduct();

        $this->actingAs($customer)->postJson('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        $item = CartItem::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($customer)
            ->patchJson("/cart/items/{$item->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJsonPath('items.0.quantity', 3);

        $this->actingAs($customer)
            ->patchJson("/cart/items/{$item->id}", ['quantity' => 0])
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('items', []);
    }

    public function test_adding_same_product_again_increases_quantity(): void
    {
        [$customer, $product] = $this->customerWithProduct();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertSame(1, CartItem::query()->where('product_id', $product->id)->count());
        $this->assertSame(2, CartItem::query()->where('product_id', $product->id)->value('quantity'));
    }

    public function test_customer_can_increase_and_decrease_cart_quantity(): void
    {
        [$customer, $product] = $this->customerWithProduct();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $item = CartItem::query()->where('product_id', $product->id)->firstOrFail();

        $this->actingAs($customer)->patch("/cart/items/{$item->id}", [
            'quantity' => 3,
        ])->assertRedirect();

        $this->assertSame(3, $item->fresh()->quantity);

        $this->actingAs($customer)->patch("/cart/items/{$item->id}", [
            'quantity' => 0,
        ])->assertRedirect();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_business_menu_payload_omits_option_values(): void
    {
        [$customer, $product] = $this->customerWithProduct();
        $business = $product->business;

        $this->actingAs($customer)
            ->get(route('customer.businesses.show', $business))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Customer/Business/Show')
                ->has('business.product_categories.0.products.0', fn ($productPage) => $productPage
                    ->where('id', $product->id)
                    ->where('has_options', false)
                    ->missing('options')
                    ->etc()));
    }
}

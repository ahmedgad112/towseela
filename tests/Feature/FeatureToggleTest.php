<?php

namespace Tests\Feature;

use App\Models\Product;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeatureToggleTest extends TestCase
{
    public function test_cart_is_forbidden_without_customer_cart_permission(): void
    {
        $customer = $this->userWithRole('customer');
        Role::findByName('customer')->revokePermissionTo('customer.cart');

        $this->actingAs($customer)
            ->get(route('customer.cart'))
            ->assertForbidden();
    }

    public function test_checkout_is_forbidden_without_customer_checkout_permission(): void
    {
        $customer = $this->userWithRole('customer');
        Role::findByName('customer')->revokePermissionTo('customer.checkout');

        $this->actingAs($customer)
            ->get(route('customer.checkout'))
            ->assertForbidden();
    }

    public function test_add_to_cart_is_forbidden_without_permission(): void
    {
        $customer = $this->userWithRole('customer');
        $product = Product::factory()->create();
        Role::findByName('customer')->revokePermissionTo('customer.cart');

        $this->actingAs($customer)
            ->post(route('customer.cart.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertForbidden();
    }

    public function test_addresses_are_forbidden_without_permission(): void
    {
        $customer = $this->userWithRole('customer');
        Role::findByName('customer')->revokePermissionTo('customer.addresses');

        $this->actingAs($customer)
            ->get(route('customer.addresses.index'))
            ->assertForbidden();
    }

    public function test_customer_with_default_permissions_can_open_cart(): void
    {
        $customer = $this->userWithRole('customer');

        $this->actingAs($customer)
            ->get(route('customer.cart'))
            ->assertOk();
    }
}

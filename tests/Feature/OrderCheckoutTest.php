<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\Coupon;
use App\Models\CustomerAddress;
use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    /**
     * @return array{0: User, 1: Product, 2: CustomerAddress}
     */
    private function catalog(): array
    {
        $this->seedRoles();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $zone = DeliveryZone::factory()->create();
        DeliveryFeeRule::factory()->create([
            'delivery_zone_id' => $zone->id,
            'min_order' => 0,
            'fee' => 15,
        ]);

        $business = Business::factory()->create(['min_order_amount' => 20]);
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'price' => 80,
        ]);

        $address = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'delivery_zone_id' => $zone->id,
        ]);

        return [$customer, $product, $address];
    }

    public function test_checkout_recalculates_totals_from_server_prices(): void
    {
        [$customer, $product, $address] = $this->catalog();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $token = (string) Str::uuid();

        $this->actingAs($customer)->post('/checkout', [
            'address_id' => $address->id,
            'checkout_token' => $token,
        ])->assertRedirect();

        $order = Order::query()->where('checkout_token', $token)->first();

        $this->assertNotNull($order);
        $this->assertSame('160.00', $order->subtotal);
        $this->assertSame('15.00', $order->delivery_fee);
        $this->assertSame('175.00', $order->grand_total);
        $this->assertSame(OrderStatus::PendingConfirmation, $order->status);
        $this->assertSame($product->name_ar, $order->items()->first()?->name);
    }

    public function test_duplicate_checkout_token_is_rejected(): void
    {
        [$customer, $product, $address] = $this->catalog();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = (string) Str::uuid();

        $this->actingAs($customer)->post('/checkout', [
            'address_id' => $address->id,
            'checkout_token' => $token,
        ])->assertRedirect();

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer)->post('/checkout', [
            'address_id' => $address->id,
            'checkout_token' => $token,
        ])->assertSessionHasErrors('checkout_token');

        $this->assertSame(1, Order::query()->where('checkout_token', $token)->count());
    }

    public function test_percent_coupon_is_applied_server_side(): void
    {
        [$customer, $product, $address] = $this->catalog();

        Coupon::query()->create([
            'code' => 'SAVE10',
            'name_ar' => 'خصم',
            'type' => 'percent',
            'value' => 10,
            'min_order' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($customer)->post('/cart/coupon', ['code' => 'SAVE10'])->assertRedirect();

        $token = (string) Str::uuid();

        $this->actingAs($customer)->post('/checkout', [
            'address_id' => $address->id,
            'checkout_token' => $token,
        ])->assertRedirect();

        $order = Order::query()->where('checkout_token', $token)->first();

        $this->assertSame('8.00', $order?->discount);
        $this->assertSame('87.00', $order?->grand_total);
    }
}

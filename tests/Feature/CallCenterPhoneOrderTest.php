<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\CustomerAddress;
use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class CallCenterPhoneOrderTest extends TestCase
{
    /**
     * @return array{0: User, 1: User, 2: Product, 3: CustomerAddress}
     */
    private function setupCatalog(): array
    {
        $agent = $this->userWithRole('call_center_agent');

        $customer = User::factory()->create([
            'name' => 'عميل الهاتف',
            'phone' => '01012131415',
        ]);
        $customer->assignRole('customer');

        $zone = DeliveryZone::factory()->create();
        DeliveryFeeRule::factory()->create([
            'delivery_zone_id' => $zone->id,
            'min_order' => 0,
            'fee' => 15,
        ]);

        $business = Business::factory()->create([
            'min_order_amount' => 20,
            'is_active' => true,
            'accepts_orders' => true,
        ]);
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'price' => 80,
            'is_active' => true,
            'is_available' => true,
        ]);

        $address = CustomerAddress::factory()->create([
            'user_id' => $customer->id,
            'delivery_zone_id' => $zone->id,
        ]);

        return [$agent, $customer, $product, $address];
    }

    public function test_call_center_agent_can_open_create_order_page(): void
    {
        $agent = $this->userWithRole('call_center_agent');

        $this->actingAs($agent)
            ->get(route('call-center.orders.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('CallCenter/Orders/Create')
                ->has('categories')
                ->has('businesses')
                ->has('zones')
                ->has('checkoutToken'));
    }

    public function test_call_center_agent_can_place_assisted_order(): void
    {
        [$agent, $customer, $product, $address] = $this->setupCatalog();
        $token = (string) Str::uuid();

        $this->actingAs($agent)
            ->post(route('call-center.orders.store'), [
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'checkout_token' => $token,
                'notes' => 'طلب من الاتصال',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $order = Order::query()->where('checkout_token', $token)->first();

        $this->assertNotNull($order);
        $this->assertSame($customer->id, $order->user_id);
        $this->assertSame(OrderStatus::PendingConfirmation, $order->status);
        $this->assertSame('160.00', $order->subtotal);
        $this->assertSame('15.00', $order->delivery_fee);
        $this->assertSame('175.00', $order->grand_total);
        $this->assertSame('طلب من الاتصال', $order->customer_notes);
        $this->assertSame(1, $order->items()->count());
    }

    public function test_dispatcher_cannot_create_phone_orders(): void
    {
        $dispatcher = $this->userWithRole('dispatcher');

        $this->actingAs($dispatcher)
            ->get(route('call-center.orders.create'))
            ->assertForbidden();
    }

    public function test_call_center_can_add_address_for_customer(): void
    {
        $agent = $this->userWithRole('call_center_agent');
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $zone = DeliveryZone::factory()->create();

        $this->actingAs($agent)
            ->postJson(route('call-center.customers.addresses.store', $customer), [
                'label' => 'home',
                'line1' => 'شارع التحرير',
                'city' => 'القاهرة',
                'delivery_zone_id' => $zone->id,
                'is_default' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('address.line1', 'شارع التحرير');

        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $customer->id,
            'line1' => 'شارع التحرير',
            'is_default' => true,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\CustomerAddress;
use App\Models\DeliveryFeeRule;
use App\Models\DeliveryZone;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderRealtime;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderRealtimePulseTest extends TestCase
{
    public function test_ops_staff_receive_pulse_after_checkout(): void
    {
        [$customer, $product, $address] = $this->catalog();
        $agent = $this->userWithRole('call_center_agent');

        $this->actingAs($customer)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertRedirect();

        $before = app(OrderRealtime::class)->ops()['v'];

        $this->actingAs($customer)->post('/checkout', [
            'address_id' => $address->id,
            'checkout_token' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertNotSame($before, app(OrderRealtime::class)->ops()['v']);
        $this->assertSame('created', app(OrderRealtime::class)->ops()['reason']);

        $this->actingAs($agent)
            ->getJson(route('realtime.orders.pulse'))
            ->assertOk()
            ->assertJsonPath('ops.reason', 'created')
            ->assertJsonPath('driver', null)
            ->assertJsonPath('customer', null);
    }

    public function test_driver_pulse_updates_on_assignment(): void
    {
        $this->seedRoles();

        $dispatcher = User::role('dispatcher')->firstOrFail();
        $driver = User::role('driver')->firstOrFail();
        DriverProfile::factory()->create([
            'user_id' => $driver->id,
            'is_available' => true,
        ]);

        $order = Order::factory()->create([
            'status' => OrderStatus::AwaitingDriver,
            'driver_id' => null,
        ]);

        $this->actingAs($dispatcher)
            ->post(route('call-center.orders.assign', $order), [
                'driver_id' => $driver->id,
            ])
            ->assertRedirect();

        $pulse = $this->actingAs($driver)
            ->getJson(route('realtime.orders.pulse'))
            ->assertOk()
            ->json('driver');

        $this->assertSame('assigned', $pulse['reason']);
        $this->assertSame($order->id, $pulse['order_id']);
    }

    public function test_customer_pulse_updates_on_status_change_and_assignment(): void
    {
        $this->seedRoles();

        $admin = User::role('admin')->firstOrFail();
        $dispatcher = User::role('dispatcher')->firstOrFail();
        $driver = User::role('driver')->firstOrFail();
        DriverProfile::factory()->create([
            'user_id' => $driver->id,
            'is_available' => true,
        ]);
        $driver->forceFill(['phone' => '01001112233'])->save();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingConfirmation,
            'driver_id' => null,
        ]);

        $before = app(OrderRealtime::class)->forCustomer($customer->id)['v'];

        $this->actingAs($admin)
            ->post(route('admin.orders.transition', $order), [
                'status' => OrderStatus::Preparing->value,
            ])
            ->assertRedirect();

        $afterStatus = app(OrderRealtime::class)->forCustomer($customer->id);
        $this->assertNotSame($before, $afterStatus['v']);
        $this->assertSame('updated', $afterStatus['reason']);
        $this->assertSame($order->id, $afterStatus['order_id']);

        $this->actingAs($admin)
            ->post(route('admin.orders.transition', $order->fresh()), [
                'status' => OrderStatus::AwaitingDriver->value,
            ])
            ->assertRedirect();

        $this->actingAs($dispatcher)
            ->post(route('call-center.orders.assign', $order->fresh()), [
                'driver_id' => $driver->id,
            ])
            ->assertRedirect();

        $pulse = $this->actingAs($customer)
            ->getJson(route('realtime.orders.pulse'))
            ->assertOk()
            ->json('customer');

        $this->assertSame('assigned', $pulse['reason']);
        $this->assertSame($order->id, $pulse['order_id']);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.driver.name', $driver->name)
                ->where('order.driver.phone', '01001112233')
                ->where('order.status', OrderStatus::AssignedToDriver->value));
    }

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
}

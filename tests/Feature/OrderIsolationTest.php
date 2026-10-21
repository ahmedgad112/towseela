<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Tests\TestCase;

class OrderIsolationTest extends TestCase
{
    public function test_customer_cannot_view_another_customer_order(): void
    {
        $this->seedRoles();

        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $stranger = User::factory()->create();
        $stranger->assignRole('customer');

        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($stranger)->get(route('customer.orders.show', $order))->assertForbidden();
    }

    public function test_customer_cannot_update_another_customer_address(): void
    {
        $this->seedRoles();

        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $stranger = User::factory()->create();
        $stranger->assignRole('customer');

        $address = CustomerAddress::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($stranger)->patch(route('customer.addresses.update', $address), [
            'label' => 'home',
            'line1' => 'عنوان مسروق',
            'city' => 'القاهرة',
            'delivery_zone_id' => $address->delivery_zone_id,
        ])->assertForbidden();
    }

    public function test_driver_cannot_view_unassigned_order(): void
    {
        $driver = $this->userWithRole('driver');
        $order = Order::factory()->create([
            'status' => OrderStatus::AwaitingDriver,
            'driver_id' => null,
        ]);

        $this->actingAs($driver)->get(route('driver.orders.show', $order))->assertForbidden();
    }
}

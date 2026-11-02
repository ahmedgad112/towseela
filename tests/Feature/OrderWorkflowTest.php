<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflowService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    public function test_invalid_transition_is_rejected(): void
    {
        $this->seedRoles();

        $admin = User::role('admin')->firstOrFail();
        $order = Order::factory()->create(['status' => OrderStatus::PendingConfirmation]);

        $this->expectException(ValidationException::class);

        app(OrderWorkflowService::class)->transition($order, OrderStatus::Delivered, $admin);
    }

    public function test_driver_cannot_mark_unassigned_order_delivered(): void
    {
        $driver = $this->userWithRole('driver');
        $order = Order::factory()->create([
            'status' => OrderStatus::OutForDelivery,
            'driver_id' => null,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.orders.deliver', $order), ['proof_of_delivery' => 'تم'])
            ->assertForbidden();
    }

    public function test_dispatcher_assigns_available_driver_and_records_history(): void
    {
        $this->seedRoles();

        $dispatcher = User::role('dispatcher')->firstOrFail();
        $driver = User::role('driver')->firstOrFail();
        DriverProfile::factory()->create([
            'user_id' => $driver->id,
            'is_available' => true,
        ]);

        $order = Order::factory()->create(['status' => OrderStatus::AwaitingDriver]);

        $this->actingAs($dispatcher)
            ->post(route('call-center.orders.assign', $order), ['driver_id' => $driver->id])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::AssignedToDriver, $order->status);
        $this->assertSame($driver->id, $order->driver_id);
        $this->assertTrue($order->statusHistories()->where('to_status', OrderStatus::AssignedToDriver)->exists());
    }

    public function test_unavailable_driver_cannot_be_assigned(): void
    {
        $this->seedRoles();

        $dispatcher = User::role('dispatcher')->firstOrFail();
        $driver = User::role('driver')->firstOrFail();
        DriverProfile::factory()->create([
            'user_id' => $driver->id,
            'is_available' => false,
        ]);

        $order = Order::factory()->create(['status' => OrderStatus::AwaitingDriver]);

        $this->actingAs($dispatcher)
            ->post(route('call-center.orders.assign', $order), ['driver_id' => $driver->id])
            ->assertSessionHasErrors('driver_id');
    }

    public function test_assigned_driver_can_confirm_delivery(): void
    {
        $this->seedRoles();

        $driver = User::role('driver')->firstOrFail();
        $order = Order::factory()->create([
            'status' => OrderStatus::OutForDelivery,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.orders.deliver', $order), ['proof_of_delivery' => 'تم التسليم'])
            ->assertRedirect(route('driver.dashboard'));

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_customer_can_cancel_pending_order_only(): void
    {
        $this->seedRoles();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $pending = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::PendingConfirmation,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $pending), ['reason' => 'تغير الرأي'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $pending->fresh()->status);

        $confirmed = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Confirmed,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $confirmed), ['reason' => 'تغير الرأي'])
            ->assertForbidden();
    }

    public function test_admin_can_cancel_and_delete_order(): void
    {
        $this->seedRoles();

        $admin = User::role('admin')->firstOrFail();
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->post(route('admin.orders.cancel', $order), ['reason' => 'طلب تجريبي'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('طلب تجريبي', $order->fresh()->cancel_reason);

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_admin_can_transition_order_status(): void
    {
        $this->seedRoles();

        $admin = User::role('admin')->firstOrFail();
        $order = Order::factory()->create(['status' => OrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->post(route('admin.orders.transition', $order), [
                'status' => OrderStatus::Preparing->value,
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
    }

    public function test_customer_cannot_cancel_after_preparing_starts(): void
    {
        $this->seedRoles();

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => OrderStatus::Preparing,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $order), ['reason' => 'تغير الرأي'])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
    }

    public function test_admin_can_move_pending_order_to_preparing(): void
    {
        $this->seedRoles();

        $admin = User::role('admin')->firstOrFail();
        $order = Order::factory()->create(['status' => OrderStatus::PendingConfirmation]);

        $this->actingAs($admin)
            ->post(route('admin.orders.transition', $order), [
                'status' => OrderStatus::Preparing->value,
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
    }

    public function test_dispatcher_cannot_cancel_via_transition(): void
    {
        $this->seedRoles();

        $dispatcher = User::role('dispatcher')->firstOrFail();
        $order = Order::factory()->create(['status' => OrderStatus::AwaitingDriver]);

        $this->actingAs($dispatcher)
            ->post(route('call-center.orders.transition', $order), [
                'status' => OrderStatus::Cancelled->value,
                'reason' => 'محاولة غير مصرح بها',
            ])
            ->assertForbidden();

        $this->assertSame(OrderStatus::AwaitingDriver, $order->fresh()->status);
    }

    public function test_staff_without_delete_permission_cannot_delete_order(): void
    {
        $this->seedRoles();

        $manager = User::role('business_manager')->firstOrFail();
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);

        $this->actingAs($manager)
            ->delete(route('admin.orders.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}

<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\OrderRealtime;
use App\Support\OrderStateMachine;
use App\Support\PlatformSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    public function __construct(
        private OrderStateMachine $stateMachine,
        private AuditLogger $audit,
        private PlatformSettings $platformSettings,
        private OrderRealtime $realtime,
    ) {}

    public function transition(Order $order, OrderStatus $to, User $actor, ?string $reason = null): Order
    {
        $updated = DB::transaction(function () use ($order, $to, $actor, $reason) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            $this->stateMachine->assertCanTransition($from, $to);

            $locked->status = $to;
            $this->stamp($locked, $to, $reason);
            $locked->save();

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $to,
                'user_id' => $actor->id,
                'reason' => $reason,
            ]);

            $action = match ($to) {
                OrderStatus::Confirmed => AuditAction::OrderConfirmed,
                OrderStatus::Cancelled => AuditAction::OrderCancelled,
                OrderStatus::DeliveryFailed => AuditAction::OrderFailed,
                default => AuditAction::OrderStatusChanged,
            };

            $this->audit->record($action, $locked, $actor, [
                'from' => $from->value,
                'to' => $to->value,
                'reason' => $reason,
            ]);

            return $locked->refresh();
        });

        $this->realtime->orderUpdated($updated);

        return $updated;
    }

    public function assignDriver(Order $order, User $driver, User $actor): Order
    {
        if (! $driver->hasRole('driver') || ! $driver->is_active) {
            throw ValidationException::withMessages([
                'driver_id' => __('المندوب غير صالح للتعيين.'),
            ]);
        }

        $profile = DriverProfile::query()->where('user_id', $driver->id)->first();

        if (! $profile?->isOperationallyAvailable()) {
            throw ValidationException::withMessages([
                'driver_id' => __('المندوب غير متاح حالياً.'),
            ]);
        }

        $updated = DB::transaction(function () use ($order, $driver, $actor) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->activeAssignment()->exists() && $locked->driver_id === $driver->id) {
                throw ValidationException::withMessages([
                    'driver_id' => __('هذا المندوب معيّن بالفعل على الطلب.'),
                ]);
            }

            if ($locked->status === OrderStatus::AssignedToDriver && $locked->driver_id) {
                $this->releaseAssignment($locked);
                $this->stateMachine->assertCanTransition($locked->status, OrderStatus::AwaitingDriver);
                $locked->status = OrderStatus::AwaitingDriver;
                $locked->driver_id = null;
                $locked->save();
            }

            $this->stateMachine->assertCanTransition($locked->status, OrderStatus::AssignedToDriver);

            $conflict = Order::query()
                ->where('driver_id', $driver->id)
                ->whereKeyNot($locked->id)
                ->whereIn('status', array_map(
                    fn (OrderStatus $status): string => $status->value,
                    array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isActiveAssignment()),
                ))
                ->lockForUpdate()
                ->count();

            if ($conflict >= $this->platformSettings->operations()['driver_active_order_limit']) {
                throw ValidationException::withMessages([
                    'driver_id' => __('المندوب وصل إلى الحد الأقصى للطلبات النشطة.'),
                ]);
            }

            $locked->activeAssignment()->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            DriverAssignment::query()->create([
                'order_id' => $locked->id,
                'driver_id' => $driver->id,
                'assigned_by' => $actor->id,
                'status' => 'active',
                'assigned_at' => now(),
            ]);

            $from = $locked->status;
            $locked->status = OrderStatus::AssignedToDriver;
            $locked->driver_id = $driver->id;
            $locked->assigned_at = now();
            $locked->save();

            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => OrderStatus::AssignedToDriver,
                'user_id' => $actor->id,
            ]);

            $this->audit->record(AuditAction::OrderAssigned, $locked, $actor, [
                'driver_id' => $driver->id,
            ]);

            return $locked->refresh();
        });

        $this->realtime->orderAssigned($updated, $driver->id);

        return $updated;
    }

    public function delete(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $this->releaseAssignment($locked);

            $this->audit->record(AuditAction::OrderDeleted, $locked, $actor, [
                'number' => $locked->number,
                'status' => $locked->status->value,
                'grand_total' => $locked->grand_total,
            ]);

            $locked->delete();
        });
    }

    public function markDelivered(Order $order, User $driver, ?string $proof = null): Order
    {
        if ($order->driver_id !== $driver->id) {
            throw ValidationException::withMessages([
                'order' => __('لا يمكنك تأكيد تسليم طلب غير معيّن لك.'),
            ]);
        }

        if ($this->platformSettings->operations()['require_proof_of_delivery_note'] && blank($proof)) {
            throw ValidationException::withMessages([
                'proof_of_delivery' => __('يرجى إدخال ملاحظة إثبات التسليم.'),
            ]);
        }

        $updated = $this->transition($order, OrderStatus::Delivered, $driver, $proof);
        $updated->update([
            'proof_of_delivery' => $proof,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $updated->payment?->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->releaseAssignment($updated);

        return $updated->refresh();
    }

    private function stamp(Order $order, OrderStatus $to, ?string $reason): void
    {
        match ($to) {
            OrderStatus::Confirmed => $order->confirmed_at = now(),
            OrderStatus::Preparing => $order->prepared_at = now(),
            OrderStatus::PickedUp => $order->picked_up_at = now(),
            OrderStatus::OutForDelivery => $order->out_for_delivery_at = now(),
            OrderStatus::Delivered => $order->delivered_at = now(),
            OrderStatus::Cancelled => tap($order, function (Order $order) use ($reason) {
                $order->cancelled_at = now();
                $order->cancel_reason = $reason;
                $this->releaseAssignment($order);
            }),
            OrderStatus::DeliveryFailed => tap($order, function (Order $order) use ($reason) {
                $order->failed_at = now();
                $order->failure_reason = $reason;
                $this->releaseAssignment($order);
            }),
            OrderStatus::AwaitingDriver => tap($order, function (Order $order) {
                $order->driver_id = null;
                $this->releaseAssignment($order);
            }),
            default => null,
        };
    }

    private function releaseAssignment(Order $order): void
    {
        $order->assignments()
            ->where('status', 'active')
            ->update([
                'status' => 'released',
                'released_at' => now(),
            ]);
    }
}

<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Business;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Order::query()->when($from, fn ($builder) => $builder->where('created_at', '>=', $from))
            ->when($to, fn ($builder) => $builder->where('created_at', '<=', $to));

        $orders = (clone $query)->get(['id', 'status', 'grand_total', 'created_at', 'delivered_at', 'cancelled_at']);

        $byStatus = collect(OrderStatus::cases())->mapWithKeys(function (OrderStatus $status) use ($orders) {
            return [$status->value => $orders->where('status', $status)->count()];
        });

        $delivered = $orders->where('status', OrderStatus::Delivered);
        $failed = $orders->where('status', OrderStatus::DeliveryFailed);
        $cancelled = $orders->where('status', OrderStatus::Cancelled);

        $averageDeliveryMinutes = $delivered
            ->filter(fn (Order $order) => $order->delivered_at && $order->created_at)
            ->avg(fn (Order $order) => $order->created_at->diffInMinutes($order->delivered_at));

        return [
            'orders_count' => $orders->count(),
            'sales_total' => $delivered->reduce(fn (string $carry, Order $order) => Money::add($carry, $order->grand_total), '0.00'),
            'pending_count' => $orders->whereIn('status', [
                OrderStatus::New,
                OrderStatus::PendingConfirmation,
            ])->count(),
            'overdue_count' => $orders->filter(fn (Order $order) => $order->isOverdue())->count(),
            'by_status' => $byStatus,
            'cancellation_rate' => $this->rate($cancelled->count(), $orders->count()),
            'failure_rate' => $this->rate($failed->count(), $orders->count()),
            'average_delivery_minutes' => $averageDeliveryMinutes ? round((float) $averageDeliveryMinutes, 1) : null,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function salesByBusiness(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return Business::query()
            ->withCount(['orders as delivered_orders_count' => function ($query) use ($from, $to) {
                $query->where('status', OrderStatus::Delivered->value)
                    ->when($from, fn ($builder) => $builder->where('created_at', '>=', $from))
                    ->when($to, fn ($builder) => $builder->where('created_at', '<=', $to));
            }])
            ->withSum(['orders as delivered_sales' => function ($query) use ($from, $to) {
                $query->where('status', OrderStatus::Delivered->value)
                    ->when($from, fn ($builder) => $builder->where('created_at', '>=', $from))
                    ->when($to, fn ($builder) => $builder->where('created_at', '<=', $to));
            }], 'grand_total')
            ->orderByDesc('delivered_sales')
            ->get()
            ->map(fn (Business $business) => [
                'id' => $business->id,
                'name' => $business->name_ar,
                'orders' => $business->delivered_orders_count,
                'sales' => Money::of($business->delivered_sales ?? 0),
            ]);
    }

    /**
     * Driver success rate = delivered / (delivered + failed + returned + cancelled after assignment).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function driverPerformance(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $drivers = User::role('driver')
            ->withCount([
                'assignedOrders as assigned_count' => fn ($query) => $this->within($query, $from, $to),
                'assignedOrders as delivered_count' => fn ($query) => $this->within($query, $from, $to)->where('status', OrderStatus::Delivered->value),
                'assignedOrders as failed_count' => fn ($query) => $this->within($query, $from, $to)->where('status', OrderStatus::DeliveryFailed->value),
                'assignedOrders as returned_count' => fn ($query) => $this->within($query, $from, $to)->where('status', OrderStatus::Returned->value),
                'assignedOrders as cancelled_count' => fn ($query) => $this->within($query, $from, $to)->where('status', OrderStatus::Cancelled->value),
                'assignedOrders as pending_count' => fn ($query) => $this->within($query, $from, $to)->whereIn('status', array_map(
                    fn (OrderStatus $status): string => $status->value,
                    OrderStatus::openStatuses(),
                )),
            ])
            ->orderBy('name')
            ->get();

        return $drivers->map(function (User $driver) {
            $denominator = $driver->delivered_count + $driver->failed_count + $driver->returned_count + $driver->cancelled_count;

            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'assigned' => $driver->assigned_count,
                'delivered' => $driver->delivered_count,
                'failed' => $driver->failed_count,
                'returned' => $driver->returned_count,
                'cancelled' => $driver->cancelled_count,
                'pending' => $driver->pending_count,
                'success_rate' => $this->rate($driver->delivered_count, $denominator),
            ];
        });
    }

    private function within($query, ?Carbon $from, ?Carbon $to)
    {
        return $query
            ->when($from, fn ($builder) => $builder->where('created_at', '>=', $from))
            ->when($to, fn ($builder) => $builder->where('created_at', '<=', $to));
    }

    private function rate(int $numerator, int $denominator): ?float
    {
        if ($denominator === 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }
}

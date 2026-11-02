<?php

namespace App\Http\Controllers\CallCenter;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlatformSettings $platformSettings): Response
    {
        abort_unless($request->user()->can('orders.view'), 403);

        $operations = $platformSettings->operations();

        $status = $request->string('status')->toString();
        $term = $request->string('q')->toString();

        $orders = Order::query()
            ->with(['customer', 'business', 'driver', 'deliveryZone'])
            ->when($status, fn ($query) => $query->where('status', $status), fn ($query) => $query->whereIn('status', [
                OrderStatus::PendingConfirmation->value,
                OrderStatus::Confirmed->value,
                OrderStatus::Preparing->value,
                OrderStatus::AwaitingDriver->value,
                OrderStatus::AssignedToDriver->value,
                OrderStatus::PickedUp->value,
                OrderStatus::OutForDelivery->value,
            ]))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($customer) => $customer
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%"));
                });
            })
            ->when($request->integer('zone'), fn ($query, $zone) => $query->where('delivery_zone_id', $zone))
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $drivers = User::role('driver')
            ->active()
            ->with('driverProfile')
            ->withCount([
                'assignedOrders as active_assignments_count' => fn ($query) => $query->whereIn('status', array_map(
                    fn (OrderStatus $item): string => $item->value,
                    array_filter(OrderStatus::cases(), fn (OrderStatus $item): bool => $item->isActiveAssignment()),
                )),
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('CallCenter/Dashboard', [
            'orders' => $orders,
            'drivers' => $drivers->map(fn (User $driver) => [
                ...$driver->only(['id', 'name', 'phone']),
                'available' => (bool) $driver->driverProfile?->is_available
                    && $driver->active_assignments_count < $operations['driver_active_order_limit'],
                'active_assignments_count' => $driver->active_assignments_count,
            ]),
            'filters' => $request->only(['q', 'status', 'zone']),
            'counts' => [
                'incoming' => Order::query()->where('status', OrderStatus::PendingConfirmation)->count(),
                'overdue' => Order::query()
                    ->whereNotIn('status', array_map(fn (OrderStatus $item) => $item->value, array_filter(OrderStatus::cases(), fn (OrderStatus $item) => $item->isTerminal())))
                    ->where('created_at', '<=', now()->subMinutes($operations['overdue_minutes']))
                    ->count(),
            ],
        ]);
    }
}

<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class OrderTransitions
{
    /**
     * @return list<array{value: string, label: string}>
     */
    public static function availableFor(User $user, Order $order): array
    {
        return Collection::make($order->status->allowedTransitions())
            ->filter(fn (OrderStatus $status): bool => self::actorMayTransitionTo($user, $order, $status))
            ->map(fn (OrderStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    public static function actorMayTransitionTo(User $user, Order $order, OrderStatus $status): bool
    {
        return match ($status) {
            OrderStatus::Cancelled => $user->can('cancel', $order),
            OrderStatus::Confirmed => $user->can('confirm', $order) || $user->can('update', $order),
            OrderStatus::Preparing => $user->can('confirm', $order) || $user->can('update', $order),
            default => $user->can('update', $order),
        };
    }
}

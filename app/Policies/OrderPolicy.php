<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view') || $user->hasRole('customer') || $user->hasRole('driver');
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('driver')) {
            return $order->driver_id === $user->id;
        }

        if ($user->hasRole('customer')) {
            return $order->user_id === $user->id;
        }

        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        return $user->can('orders.create') || $user->hasRole('customer');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->can('orders.confirm');
    }

    public function assignDriver(User $user, Order $order): bool
    {
        return $user->can('orders.assign_driver');
    }

    public function note(User $user, Order $order): bool
    {
        return $user->can('orders.note');
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($order->status->isTerminal()) {
            return false;
        }

        if ($user->can('orders.cancel')) {
            return $order->status->canTransitionTo(OrderStatus::Cancelled);
        }

        return $user->hasRole('customer')
            && $order->user_id === $user->id
            && $order->status->isCustomerCancellable();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }

    public function pickup(User $user, Order $order): bool
    {
        return $user->hasRole('driver')
            && $order->driver_id === $user->id
            && $user->can('orders.mark_delivered');
    }

    public function markDelivered(User $user, Order $order): bool
    {
        return $user->can('orders.mark_delivered')
            && ($user->hasRole('driver') ? $order->driver_id === $user->id : true);
    }
}

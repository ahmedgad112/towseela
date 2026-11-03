<?php

namespace App\Policies;

use App\Models\CustomerAddress;
use App\Models\User;

class CustomerAddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('customer')
            || $user->can('customers.view')
            || $user->can('users.view');
    }

    public function view(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id
            || $user->can('customers.view')
            || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('customer')
            || $user->can('customers.create')
            || $user->can('users.update');
    }

    public function update(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id
            || $user->can('customers.create')
            || $user->can('users.update');
    }

    public function delete(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id || $user->can('users.delete');
    }
}

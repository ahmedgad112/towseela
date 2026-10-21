<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('businesses.view');
    }

    public function view(User $user, Business $business): bool
    {
        return $user->can('businesses.view') || $business->manager_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('businesses.manage');
    }

    public function update(User $user, Business $business): bool
    {
        return $user->can('businesses.manage') || $business->manager_user_id === $user->id;
    }

    public function delete(User $user, Business $business): bool
    {
        return $user->can('businesses.manage');
    }
}

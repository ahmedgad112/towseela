<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.manage') || $user->can('businesses.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('products.manage') || $product->business?->manager_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('products.manage');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('products.manage') || $product->business?->manager_user_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('products.manage');
    }
}

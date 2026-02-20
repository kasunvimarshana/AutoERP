<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view') || $user->hasRole(['admin', 'manager', 'staff']);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && ($user->can('products.view') || $user->hasRole(['admin', 'manager', 'staff']));
    }

    public function create(User $user): bool
    {
        return $user->can('products.create') || $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && ($user->can('products.update') || $user->hasRole(['admin', 'manager']));
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && ($user->can('products.delete') || $user->hasRole(['admin']));
    }
}

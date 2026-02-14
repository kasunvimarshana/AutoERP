<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Product;

/**
 * ProductPolicy
 * 
 * Authorization policy for Product model
 */
class ProductPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'products.view') || $this->isAdmin($user);
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->isSameTenant($user, $product) && 
               ($this->hasPermission($user, 'products.view') || $this->isAdmin($user));
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'products.create') || $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->isSameTenant($user, $product) && 
               ($this->hasPermission($user, 'products.update') || $this->isAdmin($user));
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->isSameTenant($user, $product) && 
               ($this->hasPermission($user, 'products.delete') || $this->isAdmin($user));
    }

    /**
     * Determine whether the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->isSameTenant($user, $product) && $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $this->isSameTenant($user, $product) && $this->isAdmin($user);
    }
}

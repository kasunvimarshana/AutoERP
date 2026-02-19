<?php

declare(strict_types=1);

namespace Modules\Product\Policies;

use Modules\Auth\Models\User;
use Modules\Product\Models\Product;

/**
 * Product Policy
 */
class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.view');
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->hasPermission('products.view')
            && $product->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->hasPermission('products.update')
            && $product->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete')
            && $product->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete')
            && $product->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasPermission('products.delete')
            && $product->tenant_id === $user->currentTenant()->id;
    }
}

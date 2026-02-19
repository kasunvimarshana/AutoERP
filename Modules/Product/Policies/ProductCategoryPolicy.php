<?php

declare(strict_types=1);

namespace Modules\Product\Policies;

use Modules\Auth\Models\User;
use Modules\Product\Models\ProductCategory;

/**
 * Product Category Policy
 */
class ProductCategoryPolicy
{
    /**
     * Determine if the user can view any product categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('product-categories.view');
    }

    /**
     * Determine if the user can view the product category.
     */
    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('product-categories.view')
            && $productCategory->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can create product categories.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('product-categories.create');
    }

    /**
     * Determine if the user can update the product category.
     */
    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('product-categories.update')
            && $productCategory->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can delete the product category.
     */
    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('product-categories.delete')
            && $productCategory->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can restore the product category.
     */
    public function restore(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('product-categories.delete')
            && $productCategory->tenant_id === $user->currentTenant()->id;
    }

    /**
     * Determine if the user can permanently delete the product category.
     */
    public function forceDelete(User $user, ProductCategory $productCategory): bool
    {
        return $user->hasPermission('product-categories.delete')
            && $productCategory->tenant_id === $user->currentTenant()->id;
    }
}

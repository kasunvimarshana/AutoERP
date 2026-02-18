<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use App\Policies\BasePolicy;
use Modules\Inventory\Entities\Product;

/**
 * Product Policy
 *
 * Authorization policy for product management.
 * Handles CRUD operations and custom abilities like activate/deactivate products.
 */
class ProductPolicy extends BasePolicy
{
    /**
     * Permission prefix for product operations.
     */
    protected string $permissionPrefix = 'product';

    /**
     * Determine whether the user can activate the product.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function activate($user, Product $product): bool
    {
        return $this->checkPermission($user, 'activate') &&
               $this->checkTenantIsolation($user, $product) &&
               ! $product->is_active &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can deactivate the product.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function deactivate($user, Product $product): bool
    {
        return $this->checkPermission($user, 'deactivate') &&
               $this->checkTenantIsolation($user, $product) &&
               $product->is_active &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can update product pricing.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function updatePricing($user, Product $product): bool
    {
        return $this->checkPermission($user, 'update-pricing') &&
               $this->checkTenantIsolation($user, $product) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can update product stock levels.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function updateStock($user, Product $product): bool
    {
        return $this->checkPermission($user, 'update-stock') &&
               $this->checkTenantIsolation($user, $product) &&
               ($this->hasAnyRole($user, ['admin', 'inventory-manager', 'warehouse-manager']));
    }

    /**
     * Determine whether the user can view product cost information.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewCost($user, Product $product): bool
    {
        return $this->checkPermission($user, 'view-cost') &&
               $this->checkTenantIsolation($user, $product) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can duplicate the product.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function duplicate($user, Product $product): bool
    {
        return $this->checkPermission($user, 'duplicate') &&
               $this->checkTenantIsolation($user, $product);
    }

    /**
     * Determine whether the user can manage product variants.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function manageVariants($user, Product $product): bool
    {
        return $this->checkPermission($user, 'manage-variants') &&
               $this->checkTenantIsolation($user, $product) &&
               ($this->hasAnyRole($user, ['admin', 'inventory-manager']));
    }
}

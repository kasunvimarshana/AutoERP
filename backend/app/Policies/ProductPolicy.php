<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Product\Models\Product;

/**
 * Product authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation and branch-level restrictions.
 */
class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view products
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-products');
    }

    /**
     * Determine whether the user can view the product.
     * 
     * Enforces tenant isolation - users can only view products from their tenant.
     * Branch-level users can only view products accessible to their branch.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product to view
     * @return bool True if the user can view this product
     */
    public function view(User $user, Product $product): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $product->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('view-products')) {
            return false;
        }

        // Super admins can view all products in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check: if user has organization, product must belong to it
        if ($user->organization_id && $product->organization_id) {
            if ($user->organization_id !== $product->organization_id) {
                return false;
            }
        }

        // Branch-level check: if user has branch restriction, product must be accessible
        if ($user->branch_id && $product->branch_id) {
            return $user->branch_id === $product->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create products.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create products
     */
    public function create(User $user): bool
    {
        return $user->can('create-products');
    }

    /**
     * Determine whether the user can update the product.
     * 
     * Enforces tenant isolation and branch-level restrictions.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product to update
     * @return bool True if the user can update this product
     */
    public function update(User $user, Product $product): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $product->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-products')) {
            return false;
        }

        // Super admins can edit all products in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $product->organization_id) {
            if ($user->organization_id !== $product->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $product->branch_id) {
            return $user->branch_id === $product->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the product.
     * 
     * Enforces tenant isolation and branch-level restrictions.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product to delete
     * @return bool True if the user can delete this product
     */
    public function delete(User $user, Product $product): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $product->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('delete-products')) {
            return false;
        }

        // Super admins can delete all products in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $product->organization_id) {
            if ($user->organization_id !== $product->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $product->branch_id) {
            return $user->branch_id === $product->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the product.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product to restore
     * @return bool True if the user can restore this product
     */
    public function restore(User $user, Product $product): bool
    {
        // Restoring follows the same rules as updating
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the product.
     * 
     * Only super admins and admins can permanently delete products.
     * 
     * @param User $user The authenticated user
     * @param Product $product The product to permanently delete
     * @return bool True if the user can permanently delete this product
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $product->tenant_id) {
            return false;
        }

        // Only super admins and admins can force delete
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        // Check base permission
        return $user->can('delete-products');
    }
}

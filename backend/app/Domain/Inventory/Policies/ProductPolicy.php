<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Policies;

use App\Domain\Auth\Entities\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * RBAC + ABAC policy for Product domain operations.
 *
 * Roles:
 *   - super-admin  : full access across all tenants
 *   - admin        : full access within their own tenant
 *   - manager      : read + update within their own tenant
 *   - staff        : read-only within their own tenant
 *
 * ABAC attributes evaluated:
 *   - tenant_id    : resource must belong to the authenticated user's tenant
 *   - is_active    : some operations restricted to active products
 */
final class ProductPolicy
{
    use HandlesAuthorization;

    /** Any authenticated user within the same tenant may list products. */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
    }

    /** Any authenticated user within the same tenant may view a product. */
    public function view(User $user, Product $product): bool
    {
        return $this->sameOrSuperTenant($user, $product)
            && $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
    }

    /** Only admins and above may create products. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /** Admins and managers may update products. */
    public function update(User $user, Product $product): bool
    {
        return $this->sameOrSuperTenant($user, $product)
            && $user->hasAnyRole(['super-admin', 'admin', 'manager']);
    }

    /** Only admins and above may delete products. */
    public function delete(User $user, Product $product): bool
    {
        return $this->sameOrSuperTenant($user, $product)
            && $user->hasAnyRole(['super-admin', 'admin']);
    }

    /** Restore a soft-deleted product. */
    public function restore(User $user, Product $product): bool
    {
        return $this->sameOrSuperTenant($user, $product)
            && $user->hasAnyRole(['super-admin', 'admin']);
    }

    /** Adjust stock levels: admins and managers. */
    public function adjustStock(User $user, Product $product): bool
    {
        return $this->sameOrSuperTenant($user, $product)
            && $user->hasAnyRole(['super-admin', 'admin', 'manager']);
    }

    // -------------------------------------------------------------------------
    // ABAC helper
    // -------------------------------------------------------------------------

    /** Return true when the user is a super-admin or owns the same tenant as the product. */
    private function sameOrSuperTenant(User $user, Product $product): bool
    {
        return $user->hasRole('super-admin')
            || (int) $user->tenant_id === (int) $product->tenant_id;
    }
}

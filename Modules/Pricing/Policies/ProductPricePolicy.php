<?php

declare(strict_types=1);

namespace Modules\Pricing\Policies;

use Modules\Auth\Models\User;
use Modules\Pricing\Models\ProductPrice;

/**
 * ProductPricePolicy
 *
 * Authorization policy for product prices
 */
class ProductPricePolicy
{
    /**
     * Determine if user can view any product prices
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pricing.view');
    }

    /**
     * Determine if user can view a product price
     */
    public function view(User $user, ProductPrice $productPrice): bool
    {
        return $user->can('pricing.view')
            && $user->tenant_id === $productPrice->tenant_id;
    }

    /**
     * Determine if user can create product prices
     */
    public function create(User $user): bool
    {
        return $user->can('pricing.create');
    }

    /**
     * Determine if user can update a product price
     */
    public function update(User $user, ProductPrice $productPrice): bool
    {
        return $user->can('pricing.update')
            && $user->tenant_id === $productPrice->tenant_id;
    }

    /**
     * Determine if user can delete a product price
     */
    public function delete(User $user, ProductPrice $productPrice): bool
    {
        return $user->can('pricing.delete')
            && $user->tenant_id === $productPrice->tenant_id;
    }

    /**
     * Determine if user can calculate prices
     */
    public function calculate(User $user): bool
    {
        return $user->can('pricing.calculate');
    }
}

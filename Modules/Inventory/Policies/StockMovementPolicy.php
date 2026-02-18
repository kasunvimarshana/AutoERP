<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use App\Policies\BasePolicy;
use Modules\Inventory\Entities\StockMovement;

/**
 * Stock Movement Policy
 *
 * Authorization policy for stock movement management.
 * Handles CRUD operations and custom abilities like approving movements.
 */
class StockMovementPolicy extends BasePolicy
{
    /**
     * Permission prefix for stock movement operations.
     */
    protected string $permissionPrefix = 'stock-movement';

    /**
     * Determine whether the user can approve the stock movement.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approveMovement($user, StockMovement $stockMovement): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $stockMovement) &&
               $stockMovement->status === 'pending' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can reject the stock movement.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function rejectMovement($user, StockMovement $stockMovement): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $stockMovement) &&
               $stockMovement->status === 'pending' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can complete the stock movement.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function complete($user, StockMovement $stockMovement): bool
    {
        return $this->checkPermission($user, 'complete') &&
               $this->checkTenantIsolation($user, $stockMovement) &&
               $stockMovement->status === 'approved' &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can cancel the stock movement.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancel($user, StockMovement $stockMovement): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $stockMovement) &&
               ! in_array($stockMovement->status, ['completed', 'cancelled']) &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can reverse the stock movement.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reverse($user, StockMovement $stockMovement): bool
    {
        return $this->checkPermission($user, 'reverse') &&
               $this->checkTenantIsolation($user, $stockMovement) &&
               $stockMovement->status === 'completed' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can view stock movement details.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewDetails($user, StockMovement $stockMovement): bool
    {
        return $this->checkPermission($user, 'view-details') &&
               $this->checkTenantIsolation($user, $stockMovement);
    }

    /**
     * Determine whether the user can delete the stock movement (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, StockMovement $stockMovement): bool
    {
        return parent::delete($user, $stockMovement) &&
               in_array($stockMovement->status, ['draft', 'pending', 'cancelled']);
    }
}

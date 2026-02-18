<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use App\Policies\BasePolicy;
use Modules\Inventory\Entities\Warehouse;

/**
 * Warehouse Policy
 *
 * Authorization policy for warehouse management.
 * Handles CRUD operations and custom abilities like managing stock levels.
 */
class WarehousePolicy extends BasePolicy
{
    /**
     * Permission prefix for warehouse operations.
     */
    protected string $permissionPrefix = 'warehouse';

    /**
     * Determine whether the user can manage stock in the warehouse.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function manageStock($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'manage-stock') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can perform stock count in the warehouse.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function performStockCount($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'perform-stock-count') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can approve stock count adjustments.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approveStockCount($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'approve-stock-count') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can transfer stock between warehouses.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function transferStock($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'transfer-stock') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can view warehouse reports.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewReports($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'view-reports') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'warehouse-manager', 'inventory-manager']));
    }

    /**
     * Determine whether the user can configure warehouse settings.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function configureSettings($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'configure-settings') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'manager']));
    }

    /**
     * Determine whether the user can activate the warehouse.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function activate($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'activate') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'manager']));
    }

    /**
     * Determine whether the user can deactivate the warehouse.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function deactivate($user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'deactivate') &&
               $this->checkTenantIsolation($user, $warehouse) &&
               ($this->hasAnyRole($user, ['admin', 'manager']));
    }
}

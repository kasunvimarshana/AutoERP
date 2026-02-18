<?php

declare(strict_types=1);

namespace Modules\Procurement\Policies;

use App\Policies\BasePolicy;
use Modules\Procurement\Entities\Supplier;

/**
 * Supplier Policy
 *
 * Authorization policy for supplier management.
 * Handles CRUD operations and custom abilities like rating suppliers.
 */
class SupplierPolicy extends BasePolicy
{
    /**
     * Permission prefix for supplier operations.
     */
    protected string $permissionPrefix = 'supplier';

    /**
     * Determine whether the user can rate the supplier.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function rateSupplier($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'rate') &&
               $this->checkTenantIsolation($user, $supplier) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager', 'purchasing-agent']));
    }

    /**
     * Determine whether the user can activate the supplier.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function activate($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'activate') &&
               $this->checkTenantIsolation($user, $supplier) &&
               ! $supplier->is_active &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can deactivate the supplier.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function deactivate($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'deactivate') &&
               $this->checkTenantIsolation($user, $supplier) &&
               $supplier->is_active &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can approve the supplier.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $supplier) &&
               $supplier->status === 'pending' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can blacklist the supplier.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function blacklist($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'blacklist') &&
               $this->checkTenantIsolation($user, $supplier) &&
               ($this->hasAnyRole($user, ['admin', 'manager']));
    }

    /**
     * Determine whether the user can view supplier pricing.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewPricing($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'view-pricing') &&
               $this->checkTenantIsolation($user, $supplier) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager', 'purchasing-agent', 'finance-manager']));
    }

    /**
     * Determine whether the user can view supplier performance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewPerformance($user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'view-performance') &&
               $this->checkTenantIsolation($user, $supplier);
    }

    /**
     * Determine whether the user can export supplier data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Policies\BasePolicy;
use Modules\Sales\Entities\SalesOrder;

/**
 * Sales Order Policy
 *
 * Authorization policy for sales order management.
 * Handles CRUD operations and custom abilities like order confirmation and cancellation.
 */
class SalesOrderPolicy extends BasePolicy
{
    /**
     * Permission prefix for sales order operations.
     */
    protected string $permissionPrefix = 'sales-order';

    /**
     * Determine whether the user can confirm the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function confirmOrder($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'confirm') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               in_array($salesOrder->status, ['draft', 'pending']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can cancel the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancelOrder($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               ! in_array($salesOrder->status, ['cancelled', 'completed', 'invoiced']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can approve the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               $salesOrder->status === 'pending' &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can create an invoice from the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function createInvoice($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'create-invoice') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               $salesOrder->status === 'confirmed';
    }

    /**
     * Determine whether the user can schedule delivery for the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function scheduleDelivery($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'schedule-delivery') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               in_array($salesOrder->status, ['confirmed', 'approved']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'sales-manager', 'warehouse-manager']));
    }

    /**
     * Determine whether the user can update pricing on the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function updatePricing($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'update-pricing') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               in_array($salesOrder->status, ['draft', 'pending']) &&
               ($this->hasAnyRole($user, ['admin', 'sales-manager']));
    }

    /**
     * Determine whether the user can apply discount to the sales order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function applyDiscount($user, SalesOrder $salesOrder): bool
    {
        return $this->checkPermission($user, 'apply-discount') &&
               $this->checkTenantIsolation($user, $salesOrder) &&
               in_array($salesOrder->status, ['draft', 'pending']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can delete the sales order (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, SalesOrder $salesOrder): bool
    {
        return parent::delete($user, $salesOrder) &&
               $salesOrder->status === 'draft';
    }
}

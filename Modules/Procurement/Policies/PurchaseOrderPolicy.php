<?php

declare(strict_types=1);

namespace Modules\Procurement\Policies;

use App\Policies\BasePolicy;
use Modules\Procurement\Entities\PurchaseOrder;

/**
 * Purchase Order Policy
 *
 * Authorization policy for purchase order management.
 * Handles CRUD operations and custom abilities like sending, confirming, and receiving orders.
 */
class PurchaseOrderPolicy extends BasePolicy
{
    /**
     * Permission prefix for purchase order operations.
     */
    protected string $permissionPrefix = 'purchase-order';

    /**
     * Determine whether the user can send the purchase order to supplier.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function send($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'send') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['draft', 'approved']) &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager', 'purchasing-agent']));
    }

    /**
     * Determine whether the user can confirm the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function confirm($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'confirm') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['draft', 'sent']) &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can receive goods for the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function receive($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'receive') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['confirmed', 'partially_received']) &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'procurement-manager', 'purchasing-agent']));
    }

    /**
     * Determine whether the user can approve the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               $purchaseOrder->status === 'pending' &&
               ! $this->isOwner($user, $purchaseOrder) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can cancel the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancel($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               ! in_array($purchaseOrder->status, ['cancelled', 'completed', 'received']) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can create invoice from the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function createInvoice($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'create-invoice') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['received', 'partially_received', 'completed']) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can return goods from the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function returnGoods($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'return-goods') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['received', 'partially_received', 'completed']) &&
               ($this->hasAnyRole($user, ['admin', 'warehouse-manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can update pricing on the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function updatePricing($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'update-pricing') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['draft', 'pending']) &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can close the purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function close($user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'close') &&
               $this->checkTenantIsolation($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['confirmed', 'partially_received', 'received']) &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can update the purchase order (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, PurchaseOrder $purchaseOrder): bool
    {
        return parent::update($user, $purchaseOrder) &&
               in_array($purchaseOrder->status, ['draft', 'pending']);
    }

    /**
     * Determine whether the user can delete the purchase order (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, PurchaseOrder $purchaseOrder): bool
    {
        return parent::delete($user, $purchaseOrder) &&
               $purchaseOrder->status === 'draft';
    }
}

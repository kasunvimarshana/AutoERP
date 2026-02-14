<?php

namespace App\Modules\Procurement\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Procurement\Models\PurchaseOrder;

/**
 * Purchase Order Policy
 * 
 * Authorization policies for purchase order management operations
 * 
 * @package App\Modules\Procurement\Policies
 */
class PurchaseOrderPolicy
{
    /**
     * Determine whether the user can view any purchase orders
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('purchase_orders.view');
    }

    /**
     * Determine whether the user can view the purchase order
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.view') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    /**
     * Determine whether the user can create purchase orders
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('purchase_orders.create');
    }

    /**
     * Determine whether the user can update the purchase order
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.update') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    /**
     * Determine whether the user can delete the purchase order
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.delete') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    /**
     * Determine whether the user can approve the purchase order
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.approve') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    /**
     * Determine whether the user can restore the purchase order
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function restore(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.delete') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the purchase order
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function forceDelete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchase_orders.delete') && $user->tenant_id === $purchaseOrder->tenant_id;
    }
}

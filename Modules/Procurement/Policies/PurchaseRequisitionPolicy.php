<?php

declare(strict_types=1);

namespace Modules\Procurement\Policies;

use App\Policies\BasePolicy;
use Modules\Procurement\Entities\PurchaseRequisition;

/**
 * Purchase Requisition Policy
 *
 * Authorization policy for purchase requisition management.
 * Handles CRUD operations and custom abilities like approving and converting to PO.
 */
class PurchaseRequisitionPolicy extends BasePolicy
{
    /**
     * Permission prefix for purchase requisition operations.
     */
    protected string $permissionPrefix = 'purchase-requisition';

    /**
     * Determine whether the user can approve the purchase requisition.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               $purchaseRequisition->status === 'pending' &&
               ! $this->isOwner($user, $purchaseRequisition) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can reject the purchase requisition.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reject($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               $purchaseRequisition->status === 'pending' &&
               ! $this->isOwner($user, $purchaseRequisition) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can convert the purchase requisition to a purchase order.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function convertToPo($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'convert-to-po') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               $purchaseRequisition->status === 'approved' &&
               ($this->hasAnyRole($user, ['admin', 'procurement-manager', 'purchasing-agent']));
    }

    /**
     * Determine whether the user can submit the purchase requisition for approval.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function submit($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'submit') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               $purchaseRequisition->status === 'draft' &&
               ($this->isOwner($user, $purchaseRequisition) || $this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can cancel the purchase requisition.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancel($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               ! in_array($purchaseRequisition->status, ['cancelled', 'completed', 'converted']) &&
               ($this->isOwner($user, $purchaseRequisition) || $this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can resubmit the purchase requisition after rejection.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function resubmit($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'resubmit') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               $purchaseRequisition->status === 'rejected' &&
               ($this->isOwner($user, $purchaseRequisition) || $this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can assign the purchase requisition to another user.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function assign($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $this->checkPermission($user, 'assign') &&
               $this->checkTenantIsolation($user, $purchaseRequisition) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'procurement-manager']));
    }

    /**
     * Determine whether the user can update the purchase requisition (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return parent::update($user, $purchaseRequisition) &&
               in_array($purchaseRequisition->status, ['draft', 'rejected']) &&
               ($this->isOwner($user, $purchaseRequisition) || $this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }

    /**
     * Determine whether the user can delete the purchase requisition (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, PurchaseRequisition $purchaseRequisition): bool
    {
        return parent::delete($user, $purchaseRequisition) &&
               in_array($purchaseRequisition->status, ['draft', 'rejected']) &&
               ($this->isOwner($user, $purchaseRequisition) || $this->hasAnyRole($user, ['admin', 'procurement-manager']));
    }
}

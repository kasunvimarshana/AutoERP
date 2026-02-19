<?php

declare(strict_types=1);

namespace Modules\Purchase\Policies;

use Modules\Auth\Models\User;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('purchase_orders.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.view')
            && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('purchase_orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.update')
            && $user->tenant_id === $purchaseOrder->tenant_id
            && $purchaseOrder->status->canModify();
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.delete')
            && $user->tenant_id === $purchaseOrder->tenant_id
            && $purchaseOrder->status->canModify();
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.approve')
            && $user->tenant_id === $purchaseOrder->tenant_id
            && $purchaseOrder->status === PurchaseOrderStatus::PENDING;
    }

    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.send')
            && $user->tenant_id === $purchaseOrder->tenant_id
            && in_array($purchaseOrder->status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::SENT]);
    }

    public function confirm(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.confirm')
            && $user->tenant_id === $purchaseOrder->tenant_id
            && $purchaseOrder->status === PurchaseOrderStatus::SENT;
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasPermission('purchase_orders.cancel')
            && $user->tenant_id === $purchaseOrder->tenant_id
            && $purchaseOrder->status->canCancel();
    }
}

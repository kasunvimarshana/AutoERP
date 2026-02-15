<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_purchase_orders');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('view_purchase_orders') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_purchase_orders');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('update_purchase_orders') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('delete_purchase_orders') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('approve_purchase_orders') && $user->tenant_id === $purchaseOrder->tenant_id;
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('cancel_purchase_orders') && $user->tenant_id === $purchaseOrder->tenant_id;
    }
}

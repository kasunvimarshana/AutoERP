<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Procurement\Models\PurchaseReceipt;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReceiptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_purchase_receipts');
    }

    public function view(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('view_purchase_receipts') && $user->tenant_id === $purchaseReceipt->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_purchase_receipts');
    }

    public function update(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('update_purchase_receipts') && $user->tenant_id === $purchaseReceipt->tenant_id;
    }

    public function delete(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('delete_purchase_receipts') && $user->tenant_id === $purchaseReceipt->tenant_id;
    }

    public function accept(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('accept_purchase_receipts') && $user->tenant_id === $purchaseReceipt->tenant_id;
    }
}

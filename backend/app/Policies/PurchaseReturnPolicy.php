<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Procurement\Models\PurchaseReturn;
use Illuminate\Auth\Access\HandlesAuthorization;

class PurchaseReturnPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_purchase_returns');
    }

    public function view(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('view_purchase_returns') && $user->tenant_id === $purchaseReturn->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_purchase_returns');
    }

    public function update(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('update_purchase_returns') && $user->tenant_id === $purchaseReturn->tenant_id;
    }

    public function delete(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('delete_purchase_returns') && $user->tenant_id === $purchaseReturn->tenant_id;
    }

    public function approve(User $user, PurchaseReturn $purchaseReturn): bool
    {
        return $user->can('approve_purchase_returns') && $user->tenant_id === $purchaseReturn->tenant_id;
    }
}

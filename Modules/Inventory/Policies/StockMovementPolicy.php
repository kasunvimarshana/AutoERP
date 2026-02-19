<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Modules\Auth\Models\User;
use Modules\Inventory\Models\StockMovement;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('stock_movements.view');
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasPermission('stock_movements.view')
            && $user->tenant_id === $stockMovement->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('stock_movements.create');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermission('stock_movements.approve');
    }

    public function restore(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasPermission('stock_movements.delete')
            && $user->tenant_id === $stockMovement->tenant_id;
    }

    public function forceDelete(User $user, StockMovement $stockMovement): bool
    {
        return $user->hasPermission('stock_movements.force_delete')
            && $user->tenant_id === $stockMovement->tenant_id;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Modules\Auth\Models\User;
use Modules\Inventory\Models\StockItem;

class StockItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('stock_items.view');
    }

    public function view(User $user, StockItem $stockItem): bool
    {
        return $user->hasPermission('stock_items.view')
            && $user->tenant_id === $stockItem->tenant_id;
    }

    public function restore(User $user, StockItem $stockItem): bool
    {
        return $user->hasPermission('stock_items.delete')
            && $user->tenant_id === $stockItem->tenant_id;
    }

    public function forceDelete(User $user, StockItem $stockItem): bool
    {
        return $user->hasPermission('stock_items.force_delete')
            && $user->tenant_id === $stockItem->tenant_id;
    }
}

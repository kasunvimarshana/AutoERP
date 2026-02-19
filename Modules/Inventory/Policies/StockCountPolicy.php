<?php

declare(strict_types=1);

namespace Modules\Inventory\Policies;

use Modules\Auth\Models\User;
use Modules\Inventory\Models\StockCount;

class StockCountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('stock_counts.view');
    }

    public function view(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.view')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('stock_counts.create');
    }

    public function update(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.update')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function delete(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.delete')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function restore(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.delete')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function forceDelete(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.force_delete')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function start(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.start')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function complete(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.complete')
            && $user->tenant_id === $stockCount->tenant_id;
    }

    public function reconcile(User $user, StockCount $stockCount): bool
    {
        return $user->hasPermission('stock_counts.reconcile')
            && $user->tenant_id === $stockCount->tenant_id;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Allocation;

use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\AllocationMethod;

final class BatchAllocationStrategy extends AbstractAllocationStrategy
{
    protected function method(): AllocationMethod
    {
        return AllocationMethod::Batch;
    }

    protected function balanceQuery(\Modules\Inventory\DTOs\AllocationData $data): Builder
    {
        return parent::balanceQuery($data)
            ->join('inventory_batches', 'inventory_batches.id', '=', 'inventory_stock_balances.batch_id')
            ->where('inventory_batches.status', 'active')
            ->where(function (Builder $expiry): void {
                $expiry->whereNull('inventory_batches.expiry_date')
                    ->orWhereDate('inventory_batches.expiry_date', '>=', now()->toDateString());
            })
            ->select('inventory_stock_balances.*');
    }

    protected function applyOrdering(Builder $query): void
    {
        $query->orderBy('inventory_batches.id')->orderBy('inventory_stock_balances.id');
    }
}

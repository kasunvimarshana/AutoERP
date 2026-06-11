<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Allocation;

use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Enums\AllocationMethod;

final class FefoAllocationStrategy extends AbstractAllocationStrategy
{
    protected function method(): AllocationMethod
    {
        return AllocationMethod::FEFO;
    }

    protected function balanceQuery(\Modules\Inventory\DTOs\AllocationData $data): Builder
    {
        return parent::balanceQuery($data)
            ->leftJoin('inventory_batches', 'inventory_batches.id', '=', 'inventory_stock_balances.batch_id')
            ->where(function (Builder $query): void {
                $query->whereNull('inventory_batches.id')
                    ->orWhere(function (Builder $batch): void {
                        $batch->where('inventory_batches.status', 'active')
                            ->where(function (Builder $expiry): void {
                                $expiry->whereNull('inventory_batches.expiry_date')
                                    ->orWhereDate('inventory_batches.expiry_date', '>=', now()->toDateString());
                            });
                    });
            })
            ->select('inventory_stock_balances.*');
    }

    protected function applyOrdering(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN inventory_batches.expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('inventory_batches.expiry_date')
            ->orderBy('inventory_stock_balances.id');
    }
}

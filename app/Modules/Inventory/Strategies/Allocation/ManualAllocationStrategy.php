<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Allocation;

use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\Enums\AllocationMethod;

final class ManualAllocationStrategy extends AbstractAllocationStrategy
{
    protected function method(): AllocationMethod
    {
        return AllocationMethod::Manual;
    }

    protected function balanceQuery(AllocationData $data): Builder
    {
        return parent::balanceQuery($data)
            ->where('inventory_stock_balances.warehouse_location_id', $data->warehouseLocationId)
            ->where('inventory_stock_balances.batch_id', $data->batchId);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\Models\InventoryAllocation;

interface AllocationStrategyInterface
{
    public function allocate(AllocationData $data): AllocationPlanData;

    public function release(InventoryAllocation $allocation, string $quantity): AllocationPlanData;

    public function reallocate(InventoryAllocation $allocation, AllocationData $data): AllocationPlanData;

    public function preview(AllocationData $data): AllocationPlanData;
}

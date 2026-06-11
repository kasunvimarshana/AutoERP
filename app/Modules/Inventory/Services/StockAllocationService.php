<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\Models\InventoryAllocation;

final class StockAllocationService
{
    public function __construct(private readonly InventoryAllocationService $allocations) {}

    public function allocate(AllocationData $data): InventoryAllocation
    {
        return $this->allocations->allocate($data);
    }

    public function issue(InventoryAllocation $allocation, ?string $quantity = null, ?int $issuedBy = null): InventoryAllocation
    {
        return $this->allocations->issue($allocation, $quantity, $issuedBy);
    }

    public function release(InventoryAllocation $allocation, ?string $quantity = null, ?int $releasedBy = null): InventoryAllocation
    {
        return $this->allocations->release($allocation, $quantity, $releasedBy);
    }

    public function reallocate(InventoryAllocation $allocation, AllocationData $data): InventoryAllocation
    {
        return $this->allocations->reallocate($allocation, $data);
    }

    public function preview(AllocationData $data): AllocationPlanData
    {
        return $this->allocations->preview($data);
    }
}

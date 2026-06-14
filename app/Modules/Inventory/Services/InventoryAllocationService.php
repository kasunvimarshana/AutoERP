<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\AllocationPlanData;
use Modules\Inventory\Models\InventoryAllocation;

final class InventoryAllocationService
{
    public function __construct(
        private readonly InventoryAllocationCreator $creator,
        private readonly InventoryAllocationIssuer $issuer,
        private readonly InventoryAllocationReleaser $releaser,
    ) {}

    public function allocate(AllocationData $data): InventoryAllocation
    {
        return $this->creator->allocate($data);
    }

    public function issue(InventoryAllocation $allocation, ?string $quantity = null, ?int $issuedBy = null): InventoryAllocation
    {
        return $this->issuer->issue($allocation, $quantity, $issuedBy);
    }

    public function release(InventoryAllocation $allocation, ?string $quantity = null, ?int $releasedBy = null): InventoryAllocation
    {
        return $this->releaser->release($allocation, $quantity, $releasedBy);
    }

    public function reallocate(InventoryAllocation $allocation, AllocationData $data): InventoryAllocation
    {
        return DB::transaction(function () use ($allocation, $data): InventoryAllocation {
            $this->releaser->release($allocation);

            return $this->creator->allocate($data);
        });
    }

    public function preview(AllocationData $data): AllocationPlanData
    {
        return $this->creator->preview($data);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

/**
 * Represents a single line in an AllocationResult.
 *
 * Links a specific cost layer to the quantity allocated from it.
 */
class AllocationLine
{
    public function __construct(
        private readonly int $costLayerId,
        private readonly int $locationId,
        private readonly ?int $batchId,
        private readonly ?int $variantId,
        private readonly string $allocatedQuantity,
        private readonly string $unitCost,
    ) {}

    public function getCostLayerId(): int
    {
        return $this->costLayerId;
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getAllocatedQuantity(): string
    {
        return $this->allocatedQuantity;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    /**
     * Total cost = allocated_quantity * unit_cost.
     */
    public function getTotalCost(): string
    {
        return bcmul($this->allocatedQuantity, $this->unitCost, 6);
    }
}

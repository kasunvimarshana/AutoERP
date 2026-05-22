<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObjects;

final readonly class AllocationDecision
{
    public function __construct(
        public int $layerId,
        public float $quantity,
        public float $unitCost,
        public ?int $batchId = null,
        public ?int $serialId = null,
        public ?int $locationId = null,
        public ?string $meta = null,
    ) {
    }

    public function totalCost(): float
    {
        return round($this->quantity * $this->unitCost, 4);
    }
}

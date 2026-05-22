<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final readonly class AllocationLine
{
    public function __construct(
        public int $stockLevelId,
        public int $locationId,
        public ?int $batchId,
        public ?int $serialId,
        public float $quantity,
        public ?float $unitCost = null,
        public ?string $batchNumber = null,
        public ?string $lotNumber = null,
    ) {
    }
}

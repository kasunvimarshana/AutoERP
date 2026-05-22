<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final class AllocationResult
{
    /**
     * @param array<int, \Modules\Inventory\Application\DTOs\AllocationLine> $lines
     */
    public function __construct(
        public string $allocationMethod,
        public float $requestedQuantity,
        public float $allocatedQuantity,
        public array $lines = [],
    ) {
    }

    public function isFullyAllocated(): bool
    {
        return $this->allocatedQuantity >= $this->requestedQuantity;
    }
}

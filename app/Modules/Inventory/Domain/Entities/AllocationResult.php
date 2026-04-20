<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

/**
 * Immutable result of a stock allocation operation.
 *
 * Contains the individual layer allocations resolved by the allocation
 * strategy, along with aggregate totals.
 */
class AllocationResult
{
    /**
     * @param  AllocationLine[]  $lines
     */
    public function __construct(
        private readonly array $lines,
        private readonly string $totalAllocated,
        private readonly bool $fullyAllocated,
    ) {}

    /**
     * @return AllocationLine[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getTotalAllocated(): string
    {
        return $this->totalAllocated;
    }

    public function isFullyAllocated(): bool
    {
        return $this->fullyAllocated;
    }
}

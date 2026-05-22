<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\DTOs\AllocationResult;
use Modules\Inventory\Application\Support\AllocationHelper;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Enums\AllocationMethod;

final class QuantityAllocationStrategy implements AllocationStrategyInterface
{
    public function allocate(Collection $candidates, AllocationRequest $request): AllocationResult
    {
        $lines = AllocationHelper::greedyAllocate($candidates, $request->requiredQuantity);
        $allocated = array_reduce($lines, static fn (float $sum, $line): float => $sum + $line->quantity, 0.0);

        return new AllocationResult(
            allocationMethod: AllocationMethod::QUANTITY,
            requestedQuantity: $request->requiredQuantity,
            allocatedQuantity: $allocated,
            lines: $lines,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\DTOs\AllocationResult;
use Modules\Inventory\Application\Support\AllocationHelper;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Enums\AllocationMethod;

final class BatchAllocationStrategy implements AllocationStrategyInterface
{
    public function allocate(Collection $candidates, AllocationRequest $request): AllocationResult
    {
        $preferred = collect($request->preferredBatchIds)
            ->map(static fn ($id): int => (int) $id)
            ->values();

        if ($preferred->isNotEmpty()) {
            $candidates = $candidates->sortBy([
                static function ($row) use ($preferred): int {
                    $index = $preferred->search((int) ($row->batch_id ?? 0));

                    return $index === false ? 999999 : (int) $index;
                },
                static fn ($row): int => (int) ($row->stock_level_id ?? 0),
            ])->values();
        }

        $lines = AllocationHelper::greedyAllocate($candidates, $request->requiredQuantity);
        $allocated = array_reduce($lines, static fn (float $sum, $line): float => $sum + $line->quantity, 0.0);

        return new AllocationResult(
            allocationMethod: AllocationMethod::BATCH,
            requestedQuantity: $request->requiredQuantity,
            allocatedQuantity: $allocated,
            lines: $lines,
        );
    }
}

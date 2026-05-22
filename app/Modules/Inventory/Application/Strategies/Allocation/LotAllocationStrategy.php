<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\DTOs\AllocationResult;
use Modules\Inventory\Application\Support\AllocationHelper;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Enums\AllocationMethod;

final class LotAllocationStrategy implements AllocationStrategyInterface
{
    public function allocate(Collection $candidates, AllocationRequest $request): AllocationResult
    {
        $preferredLots = collect($request->preferredLotNumbers)
            ->map(static fn (string $lot): string => strtoupper(trim($lot)))
            ->filter()
            ->values();

        if ($preferredLots->isNotEmpty()) {
            $candidates = $candidates->sortBy([
                static function ($row) use ($preferredLots): int {
                    $lot = strtoupper((string) ($row->lot_number ?? ''));
                    $index = $preferredLots->search($lot);

                    return $index === false ? 999999 : (int) $index;
                },
                static fn ($row): int => (int) ($row->stock_level_id ?? 0),
            ])->values();
        }

        $lines = AllocationHelper::greedyAllocate($candidates, $request->requiredQuantity);
        $allocated = array_reduce($lines, static fn (float $sum, $line): float => $sum + $line->quantity, 0.0);

        return new AllocationResult(
            allocationMethod: AllocationMethod::LOT,
            requestedQuantity: $request->requiredQuantity,
            allocatedQuantity: $allocated,
            lines: $lines,
        );
    }
}

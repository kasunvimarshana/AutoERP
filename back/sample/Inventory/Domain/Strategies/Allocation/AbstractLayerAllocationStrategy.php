<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationResultDTO;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Contracts\AllocationStrategyContract;
use Modules\Inventory\Domain\Support\Decimal;
use Modules\Inventory\Domain\ValueObjects\AllocationDecision;

abstract class AbstractLayerAllocationStrategy implements AllocationStrategyContract
{
    public function allocate(Collection $layers, MovementLineDTO $line): AllocationResultDTO
    {
        $required = max(0.0, $line->quantity);
        $allocated = 0.0;
        $decisions = [];

        /** @var Collection<int, mixed> $ordered */
        $ordered = $this->orderedLayers($layers, $line);

        foreach ($ordered as $layer) {
            if ($allocated >= $required) {
                break;
            }

            $remaining = (float) ($layer->quantity_remaining ?? 0.0);
            if ($remaining <= 0.0) {
                continue;
            }

            $allocQty = Decimal::min(Decimal::sub($required, $allocated), $remaining);
            if ($allocQty <= 0.0) {
                continue;
            }

            $decisions[] = new AllocationDecision(
                layerId: (int) $layer->id,
                quantity: $allocQty,
                unitCost: (float) $layer->unit_cost,
                batchId: $layer->batch_id !== null ? (int) $layer->batch_id : null,
                serialId: $layer->serial_id !== null ? (int) $layer->serial_id : null,
                locationId: $layer->location_id !== null ? (int) $layer->location_id : null,
            );

            $allocated = Decimal::add($allocated, $allocQty);
        }

        return new AllocationResultDTO(
            decisions: $decisions,
            requestedQuantity: $required,
            allocatedQuantity: $allocated,
        );
    }

    abstract protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection;
}

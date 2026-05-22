<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class SerialAllocationStrategy extends FifoAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        if ($line->serialId === null) {
            return parent::orderedLayers($layers, $line);
        }

        return $layers
            ->where('serial_id', $line->serialId)
            ->values();
    }
}

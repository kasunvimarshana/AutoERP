<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class LotAllocationStrategy extends FifoAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        $lotNumber = $line->metadata['lot_number'] ?? null;
        if (!is_string($lotNumber) || $lotNumber === '') {
            return parent::orderedLayers($layers, $line);
        }

        $preferred = $layers->filter(fn ($layer): bool => (string) ($layer->batch?->lot_number ?? '') === $lotNumber);
        $fallback = $layers->reject(fn ($layer): bool => (string) ($layer->batch?->lot_number ?? '') === $lotNumber);

        return parent::orderedLayers($preferred->concat($fallback)->values(), $line);
    }
}

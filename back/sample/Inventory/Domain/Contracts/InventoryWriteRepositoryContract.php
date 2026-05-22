<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Application\DTOs\AllocationResultDTO;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovement;

interface InventoryWriteRepositoryContract
{
    /** @template T */
    public function transaction(callable $callback): mixed;

    public function createMovement(
        MovementLineDTO $line,
        float $quantityIn,
        float $quantityOut,
        float $unitCost,
        float $totalCost,
        ?AllocationResultDTO $allocation = null
    ): StockMovement;

    public function applyInbound(MovementLineDTO $line, float $unitCost): void;

    public function applyOutbound(MovementLineDTO $line, AllocationResultDTO $allocation): void;
}

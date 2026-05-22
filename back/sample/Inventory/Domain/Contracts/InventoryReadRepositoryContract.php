<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Enums\AllocationMethod;
use Modules\Inventory\Domain\Enums\ValuationMethod;

interface InventoryReadRepositoryContract
{
    public function resolveValuationMethod(MovementLineDTO $line): ValuationMethod;

    public function resolveAllocationMethod(MovementLineDTO $line): AllocationMethod;

    public function findItemBaseUomId(int $itemId): int;

    public function fetchOpenLayersForUpdate(MovementLineDTO $line): Collection;

    public function findAvailableQuantityForUpdate(MovementLineDTO $line): float;

    public function findCurrentWeightedUnitCost(MovementLineDTO $line): ?float;

    public function findReplacementUnitCost(MovementLineDTO $line): ?float;

    public function findStandardUnitCost(MovementLineDTO $line): ?float;
}

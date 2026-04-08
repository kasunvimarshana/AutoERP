<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CycleCountRepositoryInterface extends RepositoryInterface
{
    /**
     * Find all cycle counts for a given warehouse.
     */
    public function findByWarehouse(string $warehouseId): \Illuminate\Support\Collection;

    /**
     * Find all cycle count lines for a given cycle count.
     */
    public function findLines(string $cycleCountId): \Illuminate\Support\Collection;
}

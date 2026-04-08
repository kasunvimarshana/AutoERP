<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;

interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function save(Warehouse $warehouse): Warehouse;

    /**
     * Return all warehouses belonging to a given location.
     *
     * @return array<int, Warehouse>
     */
    public function getByLocation(int $locationId): array;
}

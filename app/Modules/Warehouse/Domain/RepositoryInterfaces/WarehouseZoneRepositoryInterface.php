<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

interface WarehouseZoneRepositoryInterface extends RepositoryInterface
{
    public function save(WarehouseZone $zone): WarehouseZone;

    /**
     * Return all zones belonging to a given warehouse.
     *
     * @return array<int, WarehouseZone>
     */
    public function getByWarehouse(int $warehouseId): array;

    /**
     * Move a zone node to a new parent within the nested-set tree.
     */
    public function moveNode(int $id, ?int $newParentZoneId): void;
}

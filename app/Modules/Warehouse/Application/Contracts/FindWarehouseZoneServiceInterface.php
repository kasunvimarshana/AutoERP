<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;

/**
 * Contract for querying warehouse zones.
 *
 * Separates read operations from write concerns. The additional
 * getByWarehouse() method handles zone queries scoped to a warehouse.
 */
interface FindWarehouseZoneServiceInterface extends ReadServiceInterface
{
    /**
     * Return all zones belonging to a given warehouse.
     *
     * @return array<int, \Modules\Warehouse\Domain\Entities\WarehouseZone>
     */
    public function getByWarehouse(int $warehouseId): array;
}

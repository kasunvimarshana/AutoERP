<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\StockMovement;

interface InventoryStockRepositoryInterface
{
    public function recordMovement(StockMovement $movement): StockMovement;

    public function adjustStockLevel(StockMovement $movement): void;

    public function paginateByWarehouse(
        int $tenantId,
        int $warehouseId,
        array $filters,
        int $perPage,
        int $page,
        ?string $sort = null,
    ): mixed;

    public function paginateStockLevelsByWarehouse(int $tenantId, int $warehouseId, int $perPage, int $page): mixed;

    public function locationBelongsToWarehouse(int $tenantId, int $warehouseId, int $locationId): bool;

    public function warehouseExists(int $tenantId, int $warehouseId): bool;
}

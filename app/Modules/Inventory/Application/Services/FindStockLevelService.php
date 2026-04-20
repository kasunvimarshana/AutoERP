<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\FindStockLevelServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;

class FindStockLevelService implements FindStockLevelServiceInterface
{
    public function __construct(private readonly InventoryStockRepositoryInterface $inventoryStockRepository) {}

    public function listByWarehouse(int $tenantId, int $warehouseId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->inventoryStockRepository->paginateStockLevelsByWarehouse($tenantId, $warehouseId, $perPage, $page);
    }
}

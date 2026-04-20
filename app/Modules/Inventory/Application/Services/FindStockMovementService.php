<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\FindStockMovementServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;

class FindStockMovementService implements FindStockMovementServiceInterface
{
    public function __construct(private readonly InventoryStockRepositoryInterface $inventoryStockRepository) {}

    public function listByWarehouse(
        int $tenantId,
        int $warehouseId,
        array $filters = [],
        int $perPage = 15,
        int $page = 1,
        ?string $sort = null,
    ): mixed {
        return $this->inventoryStockRepository->paginateByWarehouse($tenantId, $warehouseId, $filters, $perPage, $page, $sort);
    }
}

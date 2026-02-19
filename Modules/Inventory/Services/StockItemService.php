<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Inventory\Models\StockItem;
use Modules\Inventory\Repositories\StockItemRepository;

/**
 * Stock Item Service
 *
 * Handles business logic for stock item queries and reporting.
 */
class StockItemService
{
    public function __construct(
        private StockItemRepository $stockItemRepository
    ) {}

    /**
     * Get paginated stock items with filters.
     */
    public function getPaginatedStockItems(string $tenantId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $filters['tenant_id'] = $tenantId;
        
        return $this->stockItemRepository->searchStockItems($filters, $perPage);
    }

    /**
     * Find stock item by product and warehouse.
     */
    public function findByProductAndWarehouse(string $tenantId, string $productId, string $warehouseId): ?StockItem
    {
        $filters = [
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ];
        
        $results = $this->stockItemRepository->searchStockItems($filters, 1);
        
        return $results->first();
    }

    /**
     * Get low stock items.
     */
    public function getLowStockItems(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockItemRepository->getLowStockItems($tenantId, $perPage);
    }

    /**
     * Get stock items by warehouse with filters.
     */
    public function getByWarehouseWithFilters(string $tenantId, string $warehouseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['tenant_id'] = $tenantId;
        $filters['warehouse_id'] = $warehouseId;
        
        return $this->stockItemRepository->searchStockItems($filters, $perPage);
    }
}

<?php

namespace App\Repositories\Contracts;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all inventory records for a specific product.
     */
    public function getByProductId(string $productId): Collection;

    /**
     * Get all inventory records for a specific warehouse.
     */
    public function getByWarehouse(string $warehouseId): Collection;

    /**
     * Get items whose available quantity is at or below the low-stock threshold.
     */
    public function getLowStockItems(?int $threshold = null, ?string $tenantId = null): Collection;

    /**
     * Get inventory records with their associated stock movements eager-loaded.
     */
    public function getInventoryWithMovements(?string $tenantId = null): Collection;

    /**
     * Find a specific inventory record by product and warehouse.
     */
    public function findByProductAndWarehouse(string $productId, string $warehouseId): ?Inventory;

    /**
     * Get inventory records for multiple product IDs.
     */
    public function getByProductIds(array $productIds, ?string $tenantId = null): Collection;
}

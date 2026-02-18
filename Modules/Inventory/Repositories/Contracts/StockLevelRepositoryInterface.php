<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Inventory\Entities\StockLevel;

/**
 * Stock Level Repository Interface
 *
 * Defines the contract for stock level data access operations.
 */
interface StockLevelRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get stock level for product at warehouse.
     */
    public function getByProductAndWarehouse(int $productId, int $warehouseId): ?StockLevel;

    /**
     * Get stock level for product at specific location.
     */
    public function getByProductAndLocation(int $productId, int $locationId): ?StockLevel;

    /**
     * Get all stock levels for a product.
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get all stock levels for a warehouse.
     */
    public function getByWarehouse(int $warehouseId): Collection;

    /**
     * Get products with low stock.
     */
    public function getLowStockProducts(): Collection;

    /**
     * Get products with available stock.
     */
    public function getProductsWithAvailableStock(): Collection;
}

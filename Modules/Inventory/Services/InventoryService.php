<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Events\LowStockAlert;
use Modules\Inventory\Events\StockLevelChanged;
use Modules\Inventory\Repositories\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockLevelRepositoryInterface;

/**
 * Inventory Service
 *
 * Handles business logic for inventory availability, reservations, and stock checks.
 */
class InventoryService extends BaseService
{
    /**
     * InventoryService constructor.
     */
    public function __construct(
        protected StockLevelRepositoryInterface $stockLevelRepository,
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Check product availability at warehouse.
     */
    public function checkAvailability(int $productId, int $warehouseId, float $quantity): bool
    {
        $stockLevel = $this->stockLevelRepository->getByProductAndWarehouse($productId, $warehouseId);

        if (! $stockLevel) {
            return false;
        }

        return $stockLevel->hasAvailableStock($quantity);
    }

    /**
     * Get available quantity for product at warehouse.
     */
    public function getAvailableQuantity(int $productId, int $warehouseId): float
    {
        $stockLevel = $this->stockLevelRepository->getByProductAndWarehouse($productId, $warehouseId);

        return $stockLevel?->quantity_available ?? 0;
    }

    /**
     * Get total available quantity for product across all warehouses.
     */
    public function getTotalAvailableQuantity(int $productId): float
    {
        $stockLevels = $this->stockLevelRepository->getByProduct($productId);

        return $stockLevels->sum('quantity_available');
    }

    /**
     * Reserve stock for an order or allocation.
     */
    public function reserveStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $locationId = null
    ): bool {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $locationId) {
            $stockLevel = $locationId
                ? $this->stockLevelRepository->getByProductAndLocation($productId, $locationId)
                : $this->stockLevelRepository->getByProductAndWarehouse($productId, $warehouseId);

            if (! $stockLevel || ! $stockLevel->reserve($quantity)) {
                return false;
            }

            event(new StockLevelChanged($stockLevel));

            $this->logInfo('Stock reserved', [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
            ]);

            return true;
        });
    }

    /**
     * Release reserved stock.
     */
    public function releaseStock(
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $locationId = null
    ): void {
        DB::transaction(function () use ($productId, $warehouseId, $quantity, $locationId) {
            $stockLevel = $locationId
                ? $this->stockLevelRepository->getByProductAndLocation($productId, $locationId)
                : $this->stockLevelRepository->getByProductAndWarehouse($productId, $warehouseId);

            if ($stockLevel) {
                $stockLevel->release($quantity);
                event(new StockLevelChanged($stockLevel));

                $this->logInfo('Stock released', [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                ]);
            }
        });
    }

    /**
     * Get stock levels for a product.
     */
    public function getStockLevels(int $productId)
    {
        return $this->stockLevelRepository->getByProduct($productId);
    }

    /**
     * Get stock levels for a warehouse.
     */
    public function getWarehouseStock(int $warehouseId)
    {
        return $this->stockLevelRepository->getByWarehouse($warehouseId);
    }

    /**
     * Check for low stock and emit alerts.
     */
    public function checkLowStock(): void
    {
        $lowStockProducts = $this->stockLevelRepository->getLowStockProducts();

        foreach ($lowStockProducts as $stockLevel) {
            event(new LowStockAlert($stockLevel));
        }

        $this->logInfo('Low stock check completed', [
            'low_stock_count' => $lowStockProducts->count(),
        ]);
    }
}

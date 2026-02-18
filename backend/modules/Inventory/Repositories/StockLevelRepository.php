<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\StockLevel;

/**
 * Stock Level Repository
 *
 * Handles data access for stock levels (materialized view).
 */
class StockLevelRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return StockLevel::class;
    }

    /**
     * Get stock level for a specific product and warehouse.
     */
    public function getByProductAndWarehouse(
        string $productId,
        string $warehouseId,
        ?string $locationId = null
    ): ?StockLevel {
        $query = $this->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->first();
    }

    /**
     * Get all stock levels for a product.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByProduct(string $productId)
    {
        return $this->newQuery()
            ->where('product_id', $productId)
            ->with(['warehouse', 'location'])
            ->get();
    }

    /**
     * Get stock levels for a warehouse.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByWarehouse(string $warehouseId)
    {
        return $this->newQuery()
            ->where('warehouse_id', $warehouseId)
            ->with(['product'])
            ->get();
    }

    /**
     * Get low stock products.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLowStockProducts(int $limit = 50)
    {
        return $this->newQuery()
            ->whereNotNull('reorder_point')
            ->whereRaw('available <= reorder_point')
            ->with(['product', 'warehouse'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get total available stock for a product across all warehouses.
     */
    public function getTotalAvailableStock(string $productId): float
    {
        return (float) $this->newQuery()
            ->where('product_id', $productId)
            ->sum('available');
    }

    /**
     * Find or create stock level record.
     */
    public function findOrCreate(array $attributes): StockLevel
    {
        return $this->newQuery()->firstOrCreate(
            [
                'product_id' => $attributes['product_id'],
                'warehouse_id' => $attributes['warehouse_id'],
                'location_id' => $attributes['location_id'] ?? null,
            ],
            array_merge($attributes, [
                'available' => $attributes['available'] ?? 0,
                'reserved' => $attributes['reserved'] ?? 0,
                'allocated' => $attributes['allocated'] ?? 0,
                'damaged' => $attributes['damaged'] ?? 0,
            ])
        );
    }

    /**
     * Update stock level quantities.
     */
    public function updateQuantities(
        string $productId,
        string $warehouseId,
        ?string $locationId,
        array $quantities
    ): bool {
        $query = $this->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->update($quantities);
    }
}

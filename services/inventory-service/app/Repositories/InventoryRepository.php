<?php

namespace App\Repositories;

use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository extends BaseRepository implements InventoryRepositoryInterface
{
    public function __construct(Inventory $model)
    {
        parent::__construct($model);
    }

    // -------------------------------------------------------------------------
    // InventoryRepositoryInterface
    // -------------------------------------------------------------------------

    /**
     * Get all inventory records for a specific product.
     */
    public function getByProductId(string $productId): Collection
    {
        return $this->newQuery()
                    ->where('product_id', $productId)
                    ->with('warehouse')
                    ->get();
    }

    /**
     * Get all inventory records for a specific warehouse.
     */
    public function getByWarehouse(string $warehouseId): Collection
    {
        return $this->newQuery()
                    ->where('warehouse_id', $warehouseId)
                    ->get();
    }

    /**
     * Get items whose available quantity is at or below the low-stock threshold.
     */
    public function getLowStockItems(?int $threshold = null, ?string $tenantId = null): Collection
    {
        return $this->newQuery()
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->lowStock($threshold)
                    ->with('warehouse')
                    ->orderByRaw('(quantity - reserved_quantity) ASC')
                    ->get();
    }

    /**
     * Get inventory records with their associated stock movements eager-loaded.
     */
    public function getInventoryWithMovements(?string $tenantId = null): Collection
    {
        $query = $this->newQuery()->with(['warehouse', 'stockMovements' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(10);
        }]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Find a specific inventory record by product and warehouse.
     */
    public function findByProductAndWarehouse(string $productId, string $warehouseId): ?Inventory
    {
        return $this->newQuery()
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();
    }

    /**
     * Get inventory records for multiple product IDs.
     */
    public function getByProductIds(array $productIds, ?string $tenantId = null): Collection
    {
        $query = $this->newQuery()->whereIn('product_id', $productIds);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->with('warehouse')->get();
    }
}

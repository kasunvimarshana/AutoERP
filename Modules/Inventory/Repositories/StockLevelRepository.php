<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Entities\StockLevel;
use Modules\Inventory\Repositories\Contracts\StockLevelRepositoryInterface;

/**
 * Stock Level Repository Implementation
 *
 * Handles all stock level data access operations.
 */
class StockLevelRepository extends BaseRepository implements StockLevelRepositoryInterface
{
    /**
     * StockLevelRepository constructor.
     */
    public function __construct(StockLevel $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByProductAndWarehouse(int $productId, int $warehouseId): ?StockLevel
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByProductAndLocation(int $productId, int $locationId): ?StockLevel
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('stock_location_id', $locationId)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->with(['warehouse', 'stockLocation'])
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByWarehouse(int $warehouseId): Collection
    {
        return $this->model
            ->where('warehouse_id', $warehouseId)
            ->with(['product', 'stockLocation'])
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getLowStockProducts(): Collection
    {
        return $this->model
            ->with('product')
            ->whereHas('product', function ($query) {
                $query->whereNotNull('reorder_level');
            })
            ->get()
            ->filter(function ($stockLevel) {
                return $stockLevel->product->needsReorder();
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getProductsWithAvailableStock(): Collection
    {
        return $this->model
            ->where('quantity_available', '>', 0)
            ->with('product')
            ->get();
    }
}

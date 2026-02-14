<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\Stock;
use Illuminate\Database\Eloquent\Collection;

class StockRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Stock::class;
    }

    /**
     * Get stock by product
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)->get();
    }

    /**
     * Get stock by warehouse
     */
    public function getByWarehouse(int $warehouseId): Collection
    {
        return $this->model->where('warehouse_id', $warehouseId)->get();
    }

    /**
     * Get stock by product and warehouse
     */
    public function getByProductAndWarehouse(int $productId, int $warehouseId): ?Stock
    {
        return $this->model->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    /**
     * Get low stock items
     */
    public function getLowStock(): Collection
    {
        return $this->model->whereColumn('quantity', '<=', 'min_quantity')->get();
    }

    /**
     * Get total quantity by product
     */
    public function getTotalQuantityByProduct(int $productId): int
    {
        return $this->model->where('product_id', $productId)->sum('quantity');
    }
}

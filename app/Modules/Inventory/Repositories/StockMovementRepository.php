<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;

class StockMovementRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return StockMovement::class;
    }

    /**
     * Get movements by product
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements by warehouse
     */
    public function getByWarehouse(int $warehouseId): Collection
    {
        return $this->model->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get movements by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use Illuminate\Support\Facades\Log;

class StockMovementService extends BaseService
{
    /**
     * StockMovementService constructor
     */
    public function __construct(StockMovementRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get movements by product
     */
    public function getByProduct(int $productId)
    {
        try {
            return $this->repository->getByProduct($productId);
        } catch (\Exception $e) {
            Log::error("Error fetching movements for product {$productId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get movements by warehouse
     */
    public function getByWarehouse(int $warehouseId)
    {
        try {
            return $this->repository->getByWarehouse($warehouseId);
        } catch (\Exception $e) {
            Log::error("Error fetching movements for warehouse {$warehouseId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get movements by type
     */
    public function getByType(string $type)
    {
        try {
            return $this->repository->getByType($type);
        } catch (\Exception $e) {
            Log::error("Error fetching movements by type {$type}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get movements by date range
     */
    public function getByDateRange(string $startDate, string $endDate)
    {
        try {
            return $this->repository->getByDateRange($startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error fetching movements by date range: '.$e->getMessage());
            throw $e;
        }
    }
}

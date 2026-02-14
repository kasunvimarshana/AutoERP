<?php

namespace App\Modules\Inventory\Services;

use App\Core\BaseService;
use App\Modules\Inventory\Repositories\StockMovementRepository;
use App\Modules\Inventory\Events\StockMovementCreated;

class StockMovementService extends BaseService
{
    protected StockMovementRepository $repository;

    public function __construct(StockMovementRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createStockMovement(array $data)
    {
        return $this->transaction(function () use ($data) {
            $stockMovement = $this->repository->create($data);
            
            $this->logActivity('Stock movement created', ['stock_movement_id' => $stockMovement->id]);
            
            event(new StockMovementCreated($stockMovement));
            
            return $stockMovement;
        });
    }

    public function getStockMovement(int $id)
    {
        return $this->repository->with(['sku', 'batch', 'creator', 'tenant'])->find($id);
    }

    public function getAllStockMovements()
    {
        return $this->repository->with(['sku', 'batch', 'creator'])->all();
    }

    public function getStockMovementsBySKU(int $skuId)
    {
        return $this->repository->with(['batch', 'creator'])->getBySKU($skuId);
    }

    public function getStockMovementsByBatch(int $batchId)
    {
        return $this->repository->with(['sku', 'creator'])->getByBatch($batchId);
    }

    public function getLatestBalance(int $skuId): int
    {
        return $this->repository->getLatestBalance($skuId);
    }
}

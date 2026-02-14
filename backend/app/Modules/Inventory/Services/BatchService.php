<?php

namespace App\Modules\Inventory\Services;

use App\Core\BaseService;
use App\Modules\Inventory\Repositories\BatchRepository;
use App\Modules\Inventory\Events\BatchCreated;
use App\Modules\Inventory\Events\BatchUpdated;

class BatchService extends BaseService
{
    protected BatchRepository $repository;

    public function __construct(BatchRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createBatch(array $data)
    {
        return $this->transaction(function () use ($data) {
            $batch = $this->repository->create($data);
            
            $this->logActivity('Batch created', ['batch_id' => $batch->id]);
            
            event(new BatchCreated($batch));
            
            return $batch;
        });
    }

    public function updateBatch(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $batch = $this->repository->find($id);
                
                $this->logActivity('Batch updated', ['batch_id' => $id]);
                
                event(new BatchUpdated($batch));
            }
            
            return $result;
        });
    }

    public function getBatch(int $id)
    {
        return $this->repository->with(['sku', 'tenant'])->find($id);
    }

    public function getAllBatches()
    {
        return $this->repository->with(['sku'])->all();
    }

    public function deleteBatch(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Batch deleted', ['batch_id' => $id]);
            }
            
            return $result;
        });
    }
}

<?php

namespace App\Modules\Inventory\Services;

use App\Core\BaseService;
use App\Modules\Inventory\Repositories\SKURepository;
use App\Modules\Inventory\Events\SKUCreated;
use App\Modules\Inventory\Events\SKUUpdated;

class SKUService extends BaseService
{
    protected SKURepository $repository;

    public function __construct(SKURepository $repository)
    {
        $this->repository = $repository;
    }

    public function createSKU(array $data)
    {
        return $this->transaction(function () use ($data) {
            $sku = $this->repository->create($data);
            
            $this->logActivity('SKU created', ['sku_id' => $sku->id]);
            
            event(new SKUCreated($sku));
            
            return $sku;
        });
    }

    public function updateSKU(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $sku = $this->repository->find($id);
                
                $this->logActivity('SKU updated', ['sku_id' => $id]);
                
                event(new SKUUpdated($sku));
            }
            
            return $result;
        });
    }

    public function getSKU(int $id)
    {
        return $this->repository->with(['product', 'batches', 'tenant'])->find($id);
    }

    public function getAllSKUs()
    {
        return $this->repository->with(['product', 'batches'])->all();
    }

    public function deleteSKU(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('SKU deleted', ['sku_id' => $id]);
            }
            
            return $result;
        });
    }
}

<?php

namespace App\Modules\Procurement\Services;

use App\Core\BaseService;
use App\Modules\Procurement\Repositories\SupplierRepository;
use App\Modules\Procurement\Events\SupplierCreated;
use App\Modules\Procurement\Events\SupplierUpdated;

class SupplierService extends BaseService
{
    protected SupplierRepository $repository;

    public function __construct(SupplierRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createSupplier(array $data)
    {
        return $this->transaction(function () use ($data) {
            $supplier = $this->repository->create($data);
            
            $this->logActivity('Supplier created', ['supplier_id' => $supplier->id]);
            
            event(new SupplierCreated($supplier));
            
            return $supplier;
        });
    }

    public function updateSupplier(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $supplier = $this->repository->find($id);
                
                $this->logActivity('Supplier updated', ['supplier_id' => $id]);
                
                event(new SupplierUpdated($supplier));
            }
            
            return $result;
        });
    }

    public function getSupplier(int $id)
    {
        return $this->repository->with(['purchaseOrders', 'tenant'])->find($id);
    }

    public function getAllSuppliers()
    {
        return $this->repository->with(['tenant'])->all();
    }

    public function deleteSupplier(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Supplier deleted', ['supplier_id' => $id]);
            }
            
            return $result;
        });
    }

    public function getSuppliersByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function searchSuppliers(string $query)
    {
        return $this->repository->searchByName($query);
    }
}

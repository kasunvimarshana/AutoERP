<?php

namespace App\Modules\Procurement\Services;

use App\Core\BaseService;
use App\Modules\Procurement\Repositories\PurchaseOrderRepository;
use App\Modules\Procurement\Events\PurchaseOrderCreated;
use App\Modules\Procurement\Events\PurchaseOrderUpdated;

class PurchaseOrderService extends BaseService
{
    protected PurchaseOrderRepository $repository;

    public function __construct(PurchaseOrderRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createPurchaseOrder(array $data)
    {
        return $this->transaction(function () use ($data) {
            $purchaseOrder = $this->repository->create($data);
            
            $this->logActivity('Purchase Order created', ['purchase_order_id' => $purchaseOrder->id]);
            
            event(new PurchaseOrderCreated($purchaseOrder));
            
            return $purchaseOrder;
        });
    }

    public function updatePurchaseOrder(int $id, array $data): bool
    {
        return $this->transaction(function () use ($id, $data) {
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $purchaseOrder = $this->repository->find($id);
                
                $this->logActivity('Purchase Order updated', ['purchase_order_id' => $id]);
                
                event(new PurchaseOrderUpdated($purchaseOrder));
            }
            
            return $result;
        });
    }

    public function getPurchaseOrder(int $id)
    {
        return $this->repository->with(['supplier', 'tenant', 'approvedBy'])->find($id);
    }

    public function getAllPurchaseOrders()
    {
        return $this->repository->with(['supplier', 'tenant'])->all();
    }

    public function deletePurchaseOrder(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $result = $this->repository->delete($id);
            
            if ($result) {
                $this->logActivity('Purchase Order deleted', ['purchase_order_id' => $id]);
            }
            
            return $result;
        });
    }

    public function getPurchaseOrdersBySupplier(int $supplierId)
    {
        return $this->repository->getBySupplier($supplierId);
    }

    public function getPurchaseOrdersByStatus(string $status)
    {
        return $this->repository->getByStatus($status);
    }

    public function approvePurchaseOrder(int $id, int $userId): bool
    {
        return $this->transaction(function () use ($id, $userId) {
            $data = [
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ];
            
            $result = $this->repository->update($id, $data);
            
            if ($result) {
                $this->logActivity('Purchase Order approved', [
                    'purchase_order_id' => $id,
                    'approved_by' => $userId,
                ]);
                
                $purchaseOrder = $this->repository->find($id);
                event(new PurchaseOrderUpdated($purchaseOrder));
            }
            
            return $result;
        });
    }
}

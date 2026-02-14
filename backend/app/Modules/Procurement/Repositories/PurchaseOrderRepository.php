<?php

namespace App\Modules\Procurement\Repositories;

use App\Core\BaseRepository;
use App\Modules\Procurement\Models\PurchaseOrder;

class PurchaseOrderRepository extends BaseRepository
{
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    public function findByPoNumber(string $poNumber): ?PurchaseOrder
    {
        return $this->findBy('po_number', $poNumber);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getBySupplier(int $supplierId)
    {
        return $this->model->where('supplier_id', $supplierId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByDateRange(\DateTime $startDate, \DateTime $endDate)
    {
        return $this->model
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();
    }

    public function getPendingApproval()
    {
        return $this->model->where('status', 'submitted')->get();
    }

    public function getApprovedOrders()
    {
        return $this->model->where('status', 'approved')->get();
    }
}

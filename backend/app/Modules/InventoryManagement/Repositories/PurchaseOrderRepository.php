<?php

namespace App\Modules\InventoryManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InventoryManagement\Models\PurchaseOrder;

class PurchaseOrderRepository extends BaseRepository
{
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * Search purchase orders by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['supplier_id'])) {
            $query->where('supplier_id', $criteria['supplier_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('order_date', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('order_date', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['supplier', 'items'])
            ->orderBy('order_date', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find purchase order by PO number
     */
    public function findByPoNumber(string $poNumber): ?PurchaseOrder
    {
        return $this->model->where('po_number', $poNumber)->first();
    }

    /**
     * Get purchase orders by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['supplier', 'items'])->get();
    }

    /**
     * Get purchase orders for supplier
     */
    public function getForSupplier(int $supplierId)
    {
        return $this->model->where('supplier_id', $supplierId)
            ->with(['items'])
            ->orderBy('order_date', 'desc')
            ->get();
    }

    /**
     * Get pending purchase orders
     */
    public function getPending()
    {
        return $this->model->where('status', 'pending')->with(['supplier', 'items'])->get();
    }

    /**
     * Get approved purchase orders
     */
    public function getApproved()
    {
        return $this->model->where('status', 'approved')->with(['supplier', 'items'])->get();
    }

    /**
     * Get received purchase orders
     */
    public function getReceived()
    {
        return $this->model->where('status', 'received')->with(['supplier', 'items'])->get();
    }

    /**
     * Get overdue purchase orders
     */
    public function getOverdue()
    {
        return $this->model->where('expected_delivery_date', '<', now())
            ->whereNotIn('status', ['received', 'cancelled'])
            ->with(['supplier', 'items'])
            ->get();
    }
}

<?php

namespace App\Modules\Procurement\Repositories;

use App\Modules\Procurement\Enums\PurchaseOrderStatus;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Repositories\BaseRepository;

/**
 * Purchase Order Repository
 *
 * Handles data access for purchase orders.
 */
class PurchaseOrderRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return PurchaseOrder::class;
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
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(PurchaseOrderStatus $status)
    {
        return $this->model->byStatus($status)->with(['supplier', 'items.product'])->get();
    }

    /**
     * Get pending purchase orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPending()
    {
        return $this->model->pending()->with(['supplier', 'items.product'])->get();
    }

    /**
     * Get purchase orders by supplier
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBySupplier(int $supplierId)
    {
        return $this->model->where('supplier_id', $supplierId)
            ->with(['items.product'])
            ->orderBy('po_date', 'desc')
            ->get();
    }

    /**
     * Search purchase orders
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('po_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            })
            ->with(['supplier', 'items.product'])
            ->get();
    }

    /**
     * Find with items loaded
     */
    public function findWithItems(int $id): PurchaseOrder
    {
        return $this->model->with(['supplier', 'items.product'])->findOrFail($id);
    }

    /**
     * Get purchase orders needing approval
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNeedingApproval()
    {
        return $this->model
            ->where('status', PurchaseOrderStatus::PENDING)
            ->with(['supplier', 'items.product'])
            ->orderBy('po_date', 'asc')
            ->get();
    }
}

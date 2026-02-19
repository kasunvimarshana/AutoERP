<?php

declare(strict_types=1);

namespace Modules\Purchase\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Models\PurchaseOrder;

/**
 * Purchase Order Repository
 *
 * Handles data access operations for purchase orders.
 */
class PurchaseOrderRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new PurchaseOrder;
    }

    /**
     * Find purchase order by PO code.
     */
    public function findByPoCode(string $poCode): ?PurchaseOrder
    {
        return $this->model->where('po_code', $poCode)->first();
    }

    /**
     * Find purchase order by PO code or fail.
     */
    public function findByPoCodeOrFail(string $poCode): PurchaseOrder
    {
        $purchaseOrder = $this->findByPoCode($poCode);

        if (! $purchaseOrder) {
            throw new PurchaseOrderNotFoundException("Purchase order with code {$poCode} not found");
        }

        return $purchaseOrder;
    }

    /**
     * Get purchase orders by vendor.
     */
    public function getByVendor(string $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('vendor_id', $vendorId)
            ->with(['vendor', 'items'])
            ->latest('order_date')
            ->paginate($perPage);
    }

    /**
     * Get purchase orders by status.
     */
    public function getByStatus(PurchaseOrderStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->with(['vendor', 'items'])
            ->latest('order_date')
            ->paginate($perPage);
    }

    /**
     * Get purchase orders pending approval.
     */
    public function getPendingApproval(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', PurchaseOrderStatus::PENDING)
            ->with(['vendor', 'items'])
            ->latest('order_date')
            ->paginate($perPage);
    }

    /**
     * Get overdue purchase orders.
     */
    public function getOverdueOrders(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereIn('status', [
                PurchaseOrderStatus::CONFIRMED,
                PurchaseOrderStatus::PARTIALLY_RECEIVED,
            ])
            ->where('expected_delivery_date', '<', now())
            ->whereNull('received_at')
            ->with(['vendor', 'items'])
            ->latest('expected_delivery_date')
            ->paginate($perPage);
    }

    /**
     * Search purchase orders with filters.
     */
    public function searchPurchaseOrders(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['vendor', 'items']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        return $query->latest('order_date')->paginate($perPage);
    }

    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['vendor', 'items.product', 'items.unit']);

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('order_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('order_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('order_date')->paginate($perPage);
    }

    /**
     * Update purchase order and return the updated model.
     */
    public function update(int|string $id, array $data): PurchaseOrder
    {
        $purchaseOrder = $this->findOrFail($id);
        $purchaseOrder->update($data);

        return $purchaseOrder->fresh(['vendor', 'items']);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

class PurchaseOrderRepository extends BaseRepository implements PurchaseOrderRepositoryInterface
{
    /**
     * PurchaseOrderRepository constructor.
     */
    public function __construct(PurchaseOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * Find purchase order by code
     */
    public function findByCode(string $code): mixed
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get purchase orders by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get purchase orders by approval status
     */
    public function getByApprovalStatus(string $approvalStatus): Collection
    {
        return $this->model->where('approval_status', $approvalStatus)->get();
    }

    /**
     * Get purchase orders by payment status
     */
    public function getByPaymentStatus(string $paymentStatus): Collection
    {
        return $this->model->where('payment_status', $paymentStatus)->get();
    }

    /**
     * Get purchase orders by vendor
     */
    public function getByVendor(int $vendorId): Collection
    {
        return $this->model->where('vendor_id', $vendorId)->get();
    }

    /**
     * Get pending purchase orders
     */
    public function getPendingOrders(): Collection
    {
        return $this->model
            ->where('status', 'pending')
            ->where('approval_status', 'approved')
            ->get();
    }

    /**
     * Get overdue purchase orders
     */
    public function getOverdueOrders(): Collection
    {
        return $this->model
            ->where('status', 'pending')
            ->where('expected_delivery_date', '<', now())
            ->get();
    }

    /**
     * Search purchase orders
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by code, reference number, vendor
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('reference_number', 'like', "%{$query}%")
                  ->orWhereHas('vendor', function ($vendorQuery) use ($query) {
                      $vendorQuery->where('name', 'like', "%{$query}%")
                                  ->orWhere('code', 'like', "%{$query}%");
                  });
            });
        }

        // Apply filters
        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (isset($filters['approval_status'])) {
            $queryBuilder->where('approval_status', $filters['approval_status']);
        }

        if (isset($filters['payment_status'])) {
            $queryBuilder->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['vendor_id'])) {
            $queryBuilder->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['branch_id'])) {
            $queryBuilder->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['order_date_from'])) {
            $queryBuilder->where('order_date', '>=', $filters['order_date_from']);
        }

        if (isset($filters['order_date_to'])) {
            $queryBuilder->where('order_date', '<=', $filters['order_date_to']);
        }

        if (isset($filters['expected_delivery_from'])) {
            $queryBuilder->where('expected_delivery_date', '>=', $filters['expected_delivery_from']);
        }

        if (isset($filters['expected_delivery_to'])) {
            $queryBuilder->where('expected_delivery_date', '<=', $filters['expected_delivery_to']);
        }

        return $queryBuilder->get();
    }
}

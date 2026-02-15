<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Procurement\Models\PurchaseReceipt;
use Illuminate\Database\Eloquent\Collection;

class PurchaseReceiptRepository extends BaseRepository implements PurchaseReceiptRepositoryInterface
{
    /**
     * PurchaseReceiptRepository constructor.
     */
    public function __construct(PurchaseReceipt $model)
    {
        parent::__construct($model);
    }

    /**
     * Find purchase receipt by code
     */
    public function findByCode(string $code): mixed
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get receipts by purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId): Collection
    {
        return $this->model->where('purchase_order_id', $purchaseOrderId)->get();
    }

    /**
     * Get receipts by vendor
     */
    public function getByVendor(int $vendorId): Collection
    {
        return $this->model->where('vendor_id', $vendorId)->get();
    }

    /**
     * Get receipts by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get receipts by quality check status
     */
    public function getByQualityCheckStatus(string $qualityCheckStatus): Collection
    {
        return $this->model->where('quality_check_status', $qualityCheckStatus)->get();
    }

    /**
     * Search receipts
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by code, delivery note, invoice number
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('delivery_note_number', 'like', "%{$query}%")
                  ->orWhere('invoice_number', 'like', "%{$query}%")
                  ->orWhereHas('vendor', function ($vendorQuery) use ($query) {
                      $vendorQuery->where('name', 'like', "%{$query}%")
                                  ->orWhere('code', 'like', "%{$query}%");
                  })
                  ->orWhereHas('purchaseOrder', function ($poQuery) use ($query) {
                      $poQuery->where('code', 'like', "%{$query}%");
                  });
            });
        }

        // Apply filters
        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (isset($filters['quality_check_status'])) {
            $queryBuilder->where('quality_check_status', $filters['quality_check_status']);
        }

        if (isset($filters['vendor_id'])) {
            $queryBuilder->where('vendor_id', $filters['vendor_id']);
        }

        if (isset($filters['purchase_order_id'])) {
            $queryBuilder->where('purchase_order_id', $filters['purchase_order_id']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['branch_id'])) {
            $queryBuilder->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['receipt_date_from'])) {
            $queryBuilder->where('receipt_date', '>=', $filters['receipt_date_from']);
        }

        if (isset($filters['receipt_date_to'])) {
            $queryBuilder->where('receipt_date', '<=', $filters['receipt_date_to']);
        }

        return $queryBuilder->get();
    }
}

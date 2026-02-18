<?php

declare(strict_types=1);

namespace Modules\Purchasing\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Purchasing\Models\GoodsReceipt;

/**
 * Goods Receipt Repository
 *
 * Handles data access for goods receipts.
 */
class GoodsReceiptRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return GoodsReceipt::class;
    }

    /**
     * Find goods receipt by receipt number
     */
    public function findByReceiptNumber(string $receiptNumber): ?GoodsReceipt
    {
        return $this->newQuery()->where('receipt_number', $receiptNumber)->first();
    }

    /**
     * Get receipts by purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId): array
    {
        return $this->newQuery()
            ->where('purchase_order_id', $purchaseOrderId)
            ->with(['lineItems'])
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get receipts by supplier
     */
    public function getBySupplier(int $supplierId): array
    {
        return $this->newQuery()
            ->where('supplier_id', $supplierId)
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get receipts by warehouse
     */
    public function getByWarehouse(int $warehouseId): array
    {
        return $this->newQuery()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get receipts by status
     */
    public function getByStatus(string $status): array
    {
        return $this->newQuery()
            ->where('status', $status)
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get receipts by date range
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        return $this->newQuery()
            ->whereBetween('receipt_date', [$startDate, $endDate])
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->toArray();
    }
}

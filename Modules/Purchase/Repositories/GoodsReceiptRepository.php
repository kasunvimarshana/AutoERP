<?php

declare(strict_types=1);

namespace Modules\Purchase\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Purchase\Enums\GoodsReceiptStatus;
use Modules\Purchase\Exceptions\GoodsReceiptNotFoundException;
use Modules\Purchase\Models\GoodsReceipt;

/**
 * Goods Receipt Repository
 *
 * Handles data access operations for goods receipts.
 */
class GoodsReceiptRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new GoodsReceipt;
    }

    /**
     * Find goods receipt by GR code.
     */
    public function findByGrCode(string $grCode): ?GoodsReceipt
    {
        return $this->model->where('receipt_code', $grCode)->first();
    }

    /**
     * Find goods receipt by GR code or fail.
     */
    public function findByGrCodeOrFail(string $grCode): GoodsReceipt
    {
        $goodsReceipt = $this->findByGrCode($grCode);

        if (! $goodsReceipt) {
            throw new GoodsReceiptNotFoundException("Goods receipt with code {$grCode} not found");
        }

        return $goodsReceipt;
    }

    /**
     * Get goods receipts by purchase order.
     */
    public function getByPurchaseOrder(string $poId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('purchase_order_id', $poId)
            ->with(['vendor', 'purchaseOrder', 'items'])
            ->latest('receipt_date')
            ->paginate($perPage);
    }

    /**
     * Get goods receipts pending posting to inventory.
     */
    public function getPendingPosting(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', GoodsReceiptStatus::CONFIRMED)
            ->whereNull('posted_at')
            ->with(['vendor', 'purchaseOrder', 'items'])
            ->latest('receipt_date')
            ->paginate($perPage);
    }

    /**
     * Search goods receipts with filters.
     */
    public function searchGoodsReceipts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['vendor', 'purchaseOrder', 'items']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('receipt_code', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('delivery_note', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function ($q) use ($search) {
                        $q->where('po_code', 'like', "%{$search}%");
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

        if (! empty($filters['purchase_order_id'])) {
            $query->where('purchase_order_id', $filters['purchase_order_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('receipt_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('receipt_date', '<=', $filters['to_date']);
        }

        if (isset($filters['posted']) && $filters['posted']) {
            $query->whereNotNull('posted_at');
        }

        if (isset($filters['posted']) && ! $filters['posted']) {
            $query->whereNull('posted_at');
        }

        return $query->latest('receipt_date')->paginate($perPage);
    }

    /**
     * Update goods receipt and return the updated model.
     */
    public function update(int|string $id, array $data): GoodsReceipt
    {
        $goodsReceipt = $this->findOrFail($id);
        $goodsReceipt->update($data);

        return $goodsReceipt->fresh(['vendor', 'purchaseOrder', 'items']);
    }
}

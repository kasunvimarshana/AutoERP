<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Models\PurchaseReturnLine;

final class PurchaseDocumentLockService
{
    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, PurchaseOrder>
     */
    public function purchaseOrders(array $ids): EloquentCollection
    {
        return PurchaseOrder::query()
            ->whereIn('id', $this->ids($ids))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, PurchaseOrderLine>
     */
    public function purchaseOrderLines(array $ids): EloquentCollection
    {
        return PurchaseOrderLine::query()
            ->whereIn('id', $this->ids($ids))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, GoodsReceiptNote>
     */
    public function goodsReceipts(array $ids): EloquentCollection
    {
        return GoodsReceiptNote::query()
            ->whereIn('id', $this->ids($ids))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, GoodsReceiptNoteLine>
     */
    public function goodsReceiptLines(array $ids): EloquentCollection
    {
        return GoodsReceiptNoteLine::query()
            ->whereIn('id', $this->ids($ids))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, PurchaseReturn>
     */
    public function purchaseReturns(array $ids): EloquentCollection
    {
        return PurchaseReturn::query()
            ->whereIn('id', $this->ids($ids))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $returnIds
     * @return EloquentCollection<int, PurchaseReturnLine>
     */
    public function purchaseReturnLinesForReturns(array $returnIds): EloquentCollection
    {
        return PurchaseReturnLine::query()
            ->whereIn('purchase_return_id', $this->ids($returnIds))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return EloquentCollection<int, PurchaseReturn>
     */
    public function purchaseReturnsForGoodsReceipt(int $goodsReceiptId): EloquentCollection
    {
        return PurchaseReturn::query()
            ->where('source_type', 'goods_receipt_note')
            ->where('source_id', $goodsReceiptId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn (int $id): int => $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
        sort($normalized);

        return $normalized;
    }
}

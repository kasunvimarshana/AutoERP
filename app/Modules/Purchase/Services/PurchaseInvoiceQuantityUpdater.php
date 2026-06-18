<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseInvoiceQuantityUpdater
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseOrderQuantityService $orderQuantities,
        private readonly PurchaseStatusService $statuses,
    ) {}

    /**
     * @param  array<string, string>  $lineQuantities
     * @param  Collection<int, GoodsReceiptNote>  $goodsReceipts
     */
    public function apply(array $lineQuantities, Collection $goodsReceipts): void
    {
        foreach ($lineQuantities as $lineKey => $quantity) {
            [$lineType, $lineId] = explode(':', (string) $lineKey, 2);

            if ($lineType === 'goods_receipt_note_line') {
                $this->applyGoodsReceiptQuantity((int) $lineId, $quantity);

                continue;
            }

            if ($lineType === 'purchase_order_line') {
                $this->orderQuantities->applyInvoiced(
                    PurchaseOrderLine::query()->findOrFail((int) $lineId),
                    $quantity,
                );
            }
        }

        $this->refreshGoodsReceiptStatuses($goodsReceipts);
    }

    /**
     * @param  array<string, string>  $lineQuantities
     * @param  Collection<int, GoodsReceiptNote>  $goodsReceipts
     */
    public function reverse(array $lineQuantities, Collection $goodsReceipts): void
    {
        foreach ($lineQuantities as $lineKey => $quantity) {
            [$lineType, $lineId] = explode(':', (string) $lineKey, 2);

            if ($lineType === 'goods_receipt_note_line') {
                $this->reverseGoodsReceiptQuantity((int) $lineId, $quantity);

                continue;
            }

            if ($lineType === 'purchase_order_line') {
                $line = PurchaseOrderLine::query()->lockForUpdate()->findOrFail((int) $lineId);
                $this->orderQuantities->reverseInvoiced($line, $quantity);
            }
        }

        $this->refreshGoodsReceiptStatuses($goodsReceipts);
    }

    private function applyGoodsReceiptQuantity(int $lineId, string $quantity): void
    {
        $line = GoodsReceiptNoteLine::query()
            ->with('purchaseOrderLine')
            ->lockForUpdate()
            ->findOrFail($lineId);
        $remainingInvoiceable = $this->math->sub(
            (string) $line->accepted_quantity,
            (string) $line->invoiced_quantity,
        );
        if ($this->math->compare($quantity, $remainingInvoiceable) > 0) {
            throw new InvalidArgumentException('Purchase invoice quantity cannot exceed GRN remaining quantity.');
        }

        $line->invoiced_quantity = $this->math->add(
            (string) $line->invoiced_quantity,
            $quantity,
        );
        $line->remaining_quantity = $this->math->sub(
            (string) $line->accepted_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->status = $this->goodsReceiptLineStatus($line);
        $line->save();

        if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
            $this->orderQuantities->applyInvoiced($line->purchaseOrderLine, $quantity);
        }
    }

    private function reverseGoodsReceiptQuantity(int $lineId, string $quantity): void
    {
        $line = GoodsReceiptNoteLine::query()
            ->with('purchaseOrderLine')
            ->lockForUpdate()
            ->findOrFail($lineId);
        if ($this->math->compare($quantity, (string) $line->invoiced_quantity) > 0) {
            throw new InvalidArgumentException('Cannot reverse more invoiced quantity than currently applied.');
        }

        $line->invoiced_quantity = $this->math->sub((string) $line->invoiced_quantity, $quantity);
        $line->remaining_quantity = $this->math->sub(
            (string) $line->accepted_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->status = $this->goodsReceiptLineStatus($line);
        $line->save();

        if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
            $this->orderQuantities->reverseInvoiced($line->purchaseOrderLine, $quantity);
        }
    }

    /**
     * @param  Collection<int, GoodsReceiptNote>  $goodsReceipts
     */
    private function refreshGoodsReceiptStatuses(Collection $goodsReceipts): void
    {
        foreach ($goodsReceipts as $goodsReceipt) {
            if ($goodsReceipt instanceof GoodsReceiptNote) {
                $this->statuses->refreshGoodsReceipt($goodsReceipt->refresh()->load('lines'));
            }
        }
    }

    private function goodsReceiptLineStatus(GoodsReceiptNoteLine $line): GoodsReceiptNoteLineStatus
    {
        if ($this->math->compare((string) $line->returned_quantity, '0.000000') > 0) {
            return $this->math->compare(
                (string) $line->returned_quantity,
                (string) $line->accepted_quantity,
            ) >= 0 ? GoodsReceiptNoteLineStatus::Returned : GoodsReceiptNoteLineStatus::PartiallyReturned;
        }

        if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            return $this->math->compare(
                (string) $line->invoiced_quantity,
                (string) $line->accepted_quantity,
            ) >= 0 ? GoodsReceiptNoteLineStatus::Invoiced : GoodsReceiptNoteLineStatus::PartiallyInvoiced;
        }

        return GoodsReceiptNoteLineStatus::Posted;
    }

}

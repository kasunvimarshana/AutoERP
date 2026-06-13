<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseInvoiceQuantityUpdater
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseOrderQuantityService $orderQuantities,
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

    private function applyGoodsReceiptQuantity(int $lineId, string $quantity): void
    {
        $line = GoodsReceiptNoteLine::query()
            ->with('purchaseOrderLine')
            ->findOrFail($lineId);
        $line->invoiced_quantity = $this->math->add(
            (string) $line->invoiced_quantity,
            $quantity,
        );
        $line->remaining_quantity = $this->math->sub(
            (string) $line->accepted_quantity,
            (string) $line->invoiced_quantity,
        );
        $line->save();

        if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
            $this->orderQuantities->applyInvoiced($line->purchaseOrderLine, $quantity);
        }
    }

    /**
     * @param  Collection<int, GoodsReceiptNote>  $goodsReceipts
     */
    private function refreshGoodsReceiptStatuses(Collection $goodsReceipts): void
    {
        foreach ($goodsReceipts as $grn) {
            $grn->load('lines');
            $accepted = $this->math->sum($grn->lines->pluck('accepted_quantity')->all());
            $invoiced = $this->math->sum($grn->lines->pluck('invoiced_quantity')->all());

            if ($this->math->compare($invoiced, $accepted) >= 0) {
                $grn->status = GoodsReceiptNoteStatus::Invoiced;
            } elseif ($this->math->compare($invoiced, '0.000000') > 0) {
                $grn->status = GoodsReceiptNoteStatus::PartiallyInvoiced;
            }

            $grn->save();
        }
    }
}

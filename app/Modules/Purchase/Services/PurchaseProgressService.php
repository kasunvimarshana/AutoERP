<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseProgressService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function receiptCompletesOrder(PurchaseOrder $order, GoodsReceiptNote $currentReceipt): bool
    {
        $currentByLine = [];
        $currentReceipt->loadMissing('lines');
        foreach ($currentReceipt->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine || $line->purchase_order_line_id === null) {
                continue;
            }
            $lineId = (int) $line->purchase_order_line_id;
            $currentByLine[$lineId] = $this->math->add($currentByLine[$lineId] ?? '0.000000', (string) $line->accepted_quantity);
        }

        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            $ordered = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
            $projected = $this->math->add((string) $line->received_quantity, $currentByLine[(int) $line->getKey()] ?? '0.000000');
            if ($this->math->compare($projected, $ordered) < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, string>  $currentInvoiceQuantities  keyed as source_line_type:id
     */
    public function invoiceCompletesOrder(PurchaseOrder $order, array $currentInvoiceQuantities): bool
    {
        $currentByOrderLine = [];
        foreach ($currentInvoiceQuantities as $lineKey => $quantity) {
            [$type, $id] = explode(':', (string) $lineKey, 2);
            if ($type === 'purchase_order_line') {
                $currentByOrderLine[(int) $id] = $this->math->add($currentByOrderLine[(int) $id] ?? '0.000000', $quantity);
            }
            if ($type === 'goods_receipt_note_line') {
                $receiptLine = GoodsReceiptNoteLine::query()->with('purchaseOrderLine')->find((int) $id);
                if ($receiptLine instanceof GoodsReceiptNoteLine && $receiptLine->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $poLineId = (int) $receiptLine->purchaseOrderLine->getKey();
                    $currentByOrderLine[$poLineId] = $this->math->add($currentByOrderLine[$poLineId] ?? '0.000000', $quantity);
                }
            }
        }

        $order->loadMissing('lines');
        foreach ($order->lines as $line) {
            if (! $line instanceof PurchaseOrderLine) {
                continue;
            }
            $ordered = $this->math->sub((string) $line->ordered_quantity, (string) $line->cancelled_quantity);
            $projected = $this->math->add((string) $line->invoiced_quantity, $currentByOrderLine[(int) $line->getKey()] ?? '0.000000');
            if ($this->math->compare($projected, $ordered) < 0) {
                return false;
            }
        }

        return true;
    }
}

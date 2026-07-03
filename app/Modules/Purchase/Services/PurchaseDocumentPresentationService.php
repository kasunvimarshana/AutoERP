<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturn;

final class PurchaseDocumentPresentationService
{
    private const EMPTY_PURCHASE_ORDER_RELATED_DOCUMENTS = [
        'goods_receipts' => [],
        'supplier_invoices' => [],
        'payments' => [],
        'returns' => [],
        'debit_notes' => [],
    ];

    public function __construct(
        private readonly PurchaseProcurementBalanceService $balances,
        private readonly PurchaseDocumentCapabilityService $capabilities,
        private readonly PurchaseRelatedDocumentService $relatedDocuments,
        private readonly PurchaseAdjustmentPolicyResolver $adjustmentPolicies,
    ) {}

    public function preparePurchaseOrder(PurchaseOrder $order, bool $includeRelatedDocuments = false): PurchaseOrder
    {
        $order->loadMissing('lines');
        if ($order->relationLoaded('adjustments')) {
            $this->prepareHeaderAdjustments($order->adjustments);
        }
        $order->setAttribute('receipt_status', $this->balances->receiptStatus($order->lines));
        $order->setAttribute('invoice_status', $this->balances->purchaseOrderInvoiceStatus($order->lines));
        $order->setAttribute('return_status', $this->balances->purchaseOrderReturnStatus($order->lines));
        $order->setAttribute('capabilities', $this->capabilities->forPurchaseOrder($order));
        $order->setAttribute('related_documents', $includeRelatedDocuments
            ? $this->relatedDocuments->forPurchaseOrder($order)
            : self::EMPTY_PURCHASE_ORDER_RELATED_DOCUMENTS);

        return $order;
    }

    /**
     * @param  iterable<PurchaseOrder>  $orders
     */
    public function preparePurchaseOrders(iterable $orders, bool $includeRelatedDocuments = false): void
    {
        foreach ($orders as $order) {
            if ($order instanceof PurchaseOrder) {
                $this->preparePurchaseOrder($order, $includeRelatedDocuments);
            }
        }
    }

    public function prepareGoodsReceipt(GoodsReceiptNote $grn): GoodsReceiptNote
    {
        $grn->loadMissing('lines.purchaseOrderLine');
        if ($grn->relationLoaded('adjustments')) {
            $this->prepareHeaderAdjustments($grn->adjustments);
        }
        $grn->setAttribute('invoice_status', $this->balances->goodsReceiptInvoiceStatus($grn->lines));
        $grn->setAttribute('return_status', $this->balances->goodsReceiptReturnStatus($grn->lines));
        $grn->setAttribute('capabilities', $this->capabilities->forGoodsReceipt($grn));

        foreach ($grn->lines as $line) {
            if (! $line instanceof GoodsReceiptNoteLine) {
                continue;
            }

            $line->setAttribute(
                'remaining_invoiceable_quantity',
                $this->balances->remainingInvoiceableForGoodsReceiptLine($line),
            );
            $line->setAttribute(
                'remaining_returnable_quantity',
                $this->balances->remainingReturnableForGoodsReceiptLine($line),
            );
        }

        return $grn;
    }

    /**
     * @param  iterable<GoodsReceiptNote>  $goodsReceipts
     */
    public function prepareGoodsReceipts(iterable $goodsReceipts): void
    {
        foreach ($goodsReceipts as $grn) {
            if ($grn instanceof GoodsReceiptNote) {
                $this->prepareGoodsReceipt($grn);
            }
        }
    }

    public function preparePurchaseReturn(PurchaseReturn $return): PurchaseReturn
    {
        $return->setAttribute('capabilities', $this->capabilities->forPurchaseReturn($return));

        return $return;
    }

    /**
     * @param  iterable<PurchaseReturn>  $returns
     */
    public function preparePurchaseReturns(iterable $returns): void
    {
        foreach ($returns as $return) {
            if ($return instanceof PurchaseReturn) {
                $this->preparePurchaseReturn($return);
            }
        }
    }

    public function preparePurchaseDebitNote(PurchaseDebitNote $note): PurchaseDebitNote
    {
        $note->setAttribute('capabilities', $this->capabilities->forDebitNote($note));

        return $note;
    }

    /**
     * @param  iterable<PurchaseDebitNote>  $notes
     */
    public function preparePurchaseDebitNotes(iterable $notes): void
    {
        foreach ($notes as $note) {
            if ($note instanceof PurchaseDebitNote) {
                $this->preparePurchaseDebitNote($note);
            }
        }
    }

    private function prepareHeaderAdjustments(iterable $adjustments): void
    {
        foreach ($adjustments as $adjustment) {
            if (! is_object($adjustment) || ! method_exists($adjustment, 'setAttribute')) {
                continue;
            }

            $adjustment->setAttribute('recognition', $this->adjustmentPolicies->resolveForModel($adjustment));
        }
    }
}

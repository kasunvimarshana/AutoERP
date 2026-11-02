<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Payment\Models\PaymentAllocation;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturn;

final class PurchaseRelatedDocumentService
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function forPurchaseOrder(PurchaseOrder $order): array
    {
        $goodsReceipts = GoodsReceiptNote::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('purchase_order_id', $order->getKey())
            ->orderBy('received_date')
            ->get();

        $sourceKeys = [['purchase_order', (int) $order->getKey()]];
        foreach ($goodsReceipts as $grn) {
            $sourceKeys[] = ['goods_receipt_note', (int) $grn->getKey()];
        }

        $invoiceLinks = PurchaseInvoiceLink::query()
            ->with('invoice')
            ->where('tenant_id', $order->tenant_id)
            ->where(function ($query) use ($sourceKeys): void {
                foreach ($sourceKeys as [$type, $id]) {
                    $query->orWhere(fn ($scope) => $scope->where('source_type', $type)->where('source_id', $id));
                }
            })
            ->orderBy('id')
            ->get();

        $invoiceIds = $invoiceLinks->pluck('invoice_id')->filter()->unique()->values()->all();
        $returns = PurchaseReturn::query()
            ->where('tenant_id', $order->tenant_id)
            ->whereIn('source_id', $goodsReceipts->pluck('id')->all())
            ->where('source_type', 'goods_receipt_note')
            ->orderBy('return_date')
            ->get();

        $debitNotes = PurchaseDebitNote::query()
            ->where('tenant_id', $order->tenant_id)
            ->whereIn('purchase_return_id', $returns->pluck('id')->all())
            ->orderBy('debit_note_date')
            ->get();

        $payments = $invoiceIds === [] ? collect() : PaymentAllocation::query()
            ->with('payment')
            ->where('tenant_id', $order->tenant_id)
            ->whereIn('invoice_id', $invoiceIds)
            ->orderBy('allocation_date')
            ->get()
            ->pluck('payment')
            ->filter()
            ->unique('id')
            ->values();

        return [
            'goods_receipts' => $goodsReceipts->map(fn (GoodsReceiptNote $grn): array => [
                'id' => (int) $grn->getKey(),
                'type' => 'goods_receipt',
                'number' => $grn->grn_number,
                'date' => $grn->received_date?->toDateString(),
                'status' => $this->enumValue($grn->status),
                'url' => '/purchase/goods-receipts/'.$grn->getKey(),
            ])->all(),
            'supplier_invoices' => $invoiceLinks
                ->pluck('invoice')
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($invoice): array => [
                    'id' => (int) $invoice->getKey(),
                    'type' => 'supplier_invoice',
                    'number' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date?->toDateString(),
                    'status' => $this->enumValue($invoice->status),
                    'url' => '/invoices/'.$invoice->getKey(),
                ])
                ->all(),
            'payments' => $payments->map(fn ($payment): array => [
                'id' => (int) $payment->getKey(),
                'type' => 'supplier_payment',
                'number' => $payment->payment_number,
                'date' => $payment->payment_date?->toDateString(),
                'status' => $this->enumValue($payment->status),
                'url' => '/payments/'.$payment->getKey(),
            ])->all(),
            'returns' => $returns->map(fn (PurchaseReturn $return): array => [
                'id' => (int) $return->getKey(),
                'type' => 'purchase_return',
                'number' => $return->return_number,
                'date' => $return->return_date?->toDateString(),
                'status' => $this->enumValue($return->status),
                'url' => '/purchase/returns/'.$return->getKey(),
            ])->all(),
            'debit_notes' => $debitNotes->map(fn (PurchaseDebitNote $note): array => [
                'id' => (int) $note->getKey(),
                'type' => 'debit_note',
                'number' => $note->debit_note_number,
                'date' => $note->debit_note_date?->toDateString(),
                'status' => $this->enumValue($note->status),
                'url' => '/purchase/debit-notes/'.$note->getKey(),
            ])->all(),
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}

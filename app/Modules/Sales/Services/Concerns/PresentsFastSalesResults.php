<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Concerns;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Data\AuditEventData;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Sales\Constants\SalesAuditEvent;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesOrder;

trait PresentsFastSalesResults
{
    /** @param array<string, mixed> $resolved */
    private function previewResponse(array $resolved): array
    {
        return [
            'customer_reference' => $resolved['customer_reference'],
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $resolved['summary'],
            'customer' => $this->modelSummary($resolved['customer'], ['customer_number', 'code', 'name', 'display_name']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'documents' => [],
        ];
    }

    /** @param array<string, mixed> $resolved @param array<string, mixed> $documents */
    private function createResponse(array $resolved, array $documents): array
    {
        $order = $documents['sales_order'];
        $delivery = $documents['goods_delivery'];
        $invoice = $documents['customer_invoice'];
        $payment = $documents['customer_receipt'];
        $financePostings = $documents['finance_postings'];
        $inventoryMovements = $delivery instanceof SalesDelivery
            ? $delivery->loadMissing('lines.inventoryMovement')->lines->pluck('inventoryMovement')->filter()->values()
            : collect();
        return [
            'customer_reference' => $resolved['customer_reference'],
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $this->summaryWithReceived($resolved['summary'], $invoice, $payment),
            'customer' => $this->modelSummary($resolved['customer'], ['customer_number', 'code', 'name', 'display_name']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'documents' => [
                'sales_order' => $order instanceof SalesOrder ? $this->salesOrderRef($order) : null,
                'goods_delivery' => $delivery instanceof SalesDelivery ? $this->deliveryRef($delivery) : null,
                'inventory_transaction' => $inventoryMovements->first() instanceof InventoryMovement ? $this->inventoryMovementRef($inventoryMovements->first()) : null,
                'inventory_transactions' => $inventoryMovements->map(fn (InventoryMovement $movement): array => $this->inventoryMovementRef($movement))->all(),
                'customer_invoice' => $invoice instanceof Invoice ? $this->invoiceRef($invoice) : null,
                'customer_receipt' => $payment instanceof Payment ? $this->paymentRef($payment) : null,
                'finance_posting' => isset($financePostings[0]) ? $this->financePostingRef($financePostings[0]) : null,
                'finance_postings' => array_map(fn (PostingResultData $posting): array => $this->financePostingRef($posting), $financePostings),
            ],
        ];
    }

    /** @param array<string, string> $summary @return array<string, string> */
    private function summaryWithReceived(array $summary, ?Invoice $invoice, ?Payment $payment): array
    {
        if ($invoice instanceof Invoice) {
            $summary['received_total'] = (string) $invoice->paid_total;
            $summary['balance_due'] = (string) $invoice->balance_due;
        }
        if ($payment instanceof Payment) {
            $summary['received_total'] = (string) $payment->allocated_amount;
            $summary['balance_due'] = $this->math->sub($summary['grand_total'], (string) $payment->allocated_amount);
        }
        return $summary;
    }

    /** @param array<string, mixed> $line */
    private function linePreview(array $line): array
    {
        return [
            'line_number' => $line['line_number'],
            'item' => $this->modelSummary($line['item'], ['code', 'sku', 'name']),
            'uom' => $this->uomSummary($line),
            'description' => $line['description'],
            'is_stock' => $line['is_stock'],
            'quantity' => $line['quantity'],
            'base_quantity' => $line['base_quantity'],
            'available_quantity' => $line['available_quantity'],
            'available_base_quantity' => $line['available_base_quantity'],
            'unit_price' => $line['unit_price'],
            'price_resolution' => $line['price_resolution'],
            'discount_amount' => $line['discount_amount'],
            'tax_amount' => $line['non_withholding_tax_amount'],
            'withholding_amount' => $line['withholding_amount'],
            'line_total' => $line['line_total'],
            'taxes' => $line['taxes'],
        ];
    }

    private function salesOrderRef(SalesOrder $order): array
    {
        return ['id' => (int) $order->getKey(), 'number' => (string) $order->sales_order_number, 'status' => $this->enumValue($order->status), 'url' => '/sales/orders/'.$order->getKey()];
    }

    private function deliveryRef(SalesDelivery $delivery): array
    {
        return ['id' => (int) $delivery->getKey(), 'number' => (string) $delivery->delivery_number, 'status' => $this->enumValue($delivery->status), 'url' => '/sales/deliveries?delivery_id='.$delivery->getKey()];
    }

    private function invoiceRef(Invoice $invoice): array
    {
        return ['id' => (int) $invoice->getKey(), 'number' => (string) $invoice->invoice_number, 'status' => $this->enumValue($invoice->status), 'url' => '/invoices/'.$invoice->getKey()];
    }

    private function paymentRef(Payment $payment): array
    {
        return [
            'id' => (int) $payment->getKey(),
            'number' => (string) $payment->payment_number,
            'status' => $this->enumValue($payment->document_status),
            'posting_status' => $this->enumValue($payment->posting_status),
            'finance_posting_reference' => $payment->finance_posting_reference,
            'url' => '/payments/'.$payment->getKey(),
        ];
    }

    private function inventoryMovementRef(InventoryMovement $movement): array
    {
        return ['id' => (int) $movement->getKey(), 'number' => (string) $movement->movement_number, 'status' => $this->enumValue($movement->status), 'url' => '/inventory?movement_id='.$movement->getKey()];
    }

    private function financePostingRef(PostingResultData $posting): array
    {
        return ['id' => $posting->journalId, 'number' => $posting->journalNumber, 'status' => $posting->status, 'url' => '/finance/journals/'.$posting->journalId, 'total_debit' => $posting->totalDebit, 'total_credit' => $posting->totalCredit];
    }

    /** @param list<string> $fields */
    private function modelSummary(mixed $model, array $fields): ?array
    {
        if (! is_object($model) || ! method_exists($model, 'getKey')) {
            return null;
        }
        $data = ['id' => (int) $model->getKey()];
        foreach ($fields as $field) {
            if (($model->{$field} ?? null) !== null && $model->{$field} !== '') {
                $data[$field] = $model->{$field};
            }
        }
        if (! isset($data['name']) && isset($data['display_name'])) {
            $data['name'] = $data['display_name'];
        }
        return $data;
    }

    /** @param array<string, mixed> $line */
    private function uomSummary(array $line): ?array
    {
        $uomId = $this->nullableInt($line['uom_id'] ?? null);
        if ($uomId === null) {
            return null;
        }
        $model = DB::table('unit_of_measures')
            ->where('tenant_id', (int) $line['item']->tenant_id)
            ->where('id', $uomId)
            ->first(['id', 'code', 'name', 'symbol']);
        if ($model === null) {
            return null;
        }
        return ['id' => (int) $model->id, 'code' => $model->code, 'name' => $model->name, 'symbol' => $model->symbol];
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    /** @param array<string, mixed> $resolved @param array<string, mixed> $response */
    private function writeAuditLog(array $resolved, string $referenceHash, string $requestHash, array $response): void
    {
        $documents = is_array($response['documents'] ?? null) ? $response['documents'] : [];
        $this->audit->record(new AuditEventData(
            eventName: SalesAuditEvent::FAST_SALES_COMPLETED,
            eventCategory: AuditEventCategory::FINANCIAL,
            sourceModule: 'sales',
            subjectType: 'fast_sales',
            subjectId: $referenceHash,
            subjectReference: (string) $resolved['customer_reference'],
            sourceType: 'fast_sales',
            sourceId: $referenceHash,
            sourceReference: (string) $resolved['customer_reference'],
            metadata: [
                'request_hash' => $requestHash,
                'customer_id' => (int) $resolved['customer']->getKey(),
                'transaction_date' => (string) $resolved['transaction_date'],
                'summary' => $response['summary'] ?? [],
                'documents' => $this->auditDocumentReferences($documents),
            ],
            tags: ['sales', 'fast_sales'],
            producerKey: 'fast_sales.completed:'.$referenceHash.':'.$requestHash,
        ));
    }

    /** @param array<string, mixed> $documents */
    private function auditDocumentReferences(array $documents): array
    {
        $result = [];
        foreach (['sales_order', 'goods_delivery', 'customer_invoice', 'customer_receipt', 'inventory_transaction', 'finance_posting'] as $key) {
            if (is_array($documents[$key] ?? null)) {
                $result[$key] = $documents[$key];
            }
        }
        $result['inventory_transaction_count'] = is_array($documents['inventory_transactions'] ?? null) ? count($documents['inventory_transactions']) : 0;
        $result['finance_posting_count'] = is_array($documents['finance_postings'] ?? null) ? count($documents['finance_postings']) : 0;
        return $result;
    }

    /** @param array<string, mixed> $documents */
    private function documentIds(array $documents): array
    {
        return [
            'sales_order_id' => $documents['sales_order'] instanceof SalesOrder ? (int) $documents['sales_order']->getKey() : null,
            'sales_delivery_id' => $documents['goods_delivery'] instanceof SalesDelivery ? (int) $documents['goods_delivery']->getKey() : null,
            'customer_invoice_id' => $documents['customer_invoice'] instanceof Invoice ? (int) $documents['customer_invoice']->getKey() : null,
            'customer_receipt_id' => $documents['customer_receipt'] instanceof Payment ? (int) $documents['customer_receipt']->getKey() : null,
        ];
    }
}

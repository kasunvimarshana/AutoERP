<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseOrder;

final class FastPurchaseResponseBuilder
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    public function preview(array $resolved): array
    {
        return [
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $resolved['summary'],
            'supplier' => $this->modelSummary($resolved['supplier'], ['supplier_number', 'code', 'name', 'display_name']),
            'adjustments' => $this->adjustmentPreview($resolved['adjustments']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'document_plan' => $this->documentPlan($resolved),
            'documents' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    public function created(array $resolved, array $documents): array
    {
        /** @var PurchaseOrder|null $purchaseOrder */
        $purchaseOrder = $documents['purchase_order'];
        /** @var GoodsReceiptNote|null $goodsReceipt */
        $goodsReceipt = $documents['goods_receipt'];
        /** @var Invoice|null $invoice */
        $invoice = $documents['supplier_invoice'];
        /** @var Payment|null $payment */
        $payment = $documents['supplier_payment'];
        $financePostings = $documents['finance_postings'];
        $inventoryMovements = $goodsReceipt instanceof GoodsReceiptNote
            ? $goodsReceipt->lines->pluck('inventoryMovement')->filter()->values()
            : collect();

        return [
            'supplier_reference' => $resolved['supplier_reference'],
            'mode' => $resolved['mode'],
            'options' => $resolved['options'],
            'summary' => $this->summaryWithPaid($resolved['summary'], $invoice, $payment),
            'supplier' => $this->modelSummary($resolved['supplier'], ['supplier_number', 'code', 'name', 'display_name']),
            'adjustments' => $this->adjustmentPreview($resolved['adjustments']),
            'lines' => array_map(fn (array $line): array => $this->linePreview($line), $resolved['lines']),
            'document_plan' => $this->documentPlan($resolved),
            'documents' => [
                'purchase_order' => $purchaseOrder instanceof PurchaseOrder ? $this->purchaseOrderRef($purchaseOrder) : null,
                'goods_receipt' => $goodsReceipt instanceof GoodsReceiptNote ? $this->goodsReceiptRef($goodsReceipt) : null,
                'supplier_invoice' => $invoice instanceof Invoice ? $this->invoiceRef($invoice) : null,
                'supplier_payment' => $payment instanceof Payment ? $this->paymentRef($payment) : null,
                'inventory_transaction' => $inventoryMovements->first() instanceof InventoryMovement ? $this->inventoryMovementRef($inventoryMovements->first()) : null,
                'inventory_transactions' => $inventoryMovements->map(fn (InventoryMovement $movement): array => $this->inventoryMovementRef($movement))->all(),
                'finance_posting' => isset($financePostings[0]) ? $this->financePostingRef($financePostings[0]) : null,
                'finance_postings' => array_map(fn (PostingResultData $posting): array => $this->financePostingRef($posting), $financePostings),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, string>
     */
    private function summaryWithPaid(array $summary, ?Invoice $invoice, ?Payment $payment): array
    {
        if ($invoice instanceof Invoice) {
            $summary['paid_total'] = (string) $invoice->paid_total;
            $summary['balance_due'] = (string) $invoice->balance_due;
        }
        if ($payment instanceof Payment) {
            $summary['paid_total'] = (string) $payment->allocated_amount;
            $summary['balance_due'] = $this->math->sub($summary['grand_total'], (string) $payment->allocated_amount);
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function linePreview(array $line): array
    {
        return [
            'client_line_key' => $line['client_line_key'] ?? null,
            'line_number' => $line['line_number'],
            'item' => $this->modelSummary($line['item'], ['code', 'sku', 'name']),
            'uom' => $this->modelSummary($line['uom'], ['code', 'name', 'symbol']),
            'description' => $line['description'],
            'is_stock' => $line['is_stock'],
            'quantity' => $line['quantity'],
            'base_quantity' => $line['base_quantity'],
            'unit_cost' => $line['unit_cost'],
            'pricing_mode' => $line['pricing_mode'] ?? 'manual',
            'price_source' => $line['price_source'] ?? null,
            'price_source_id' => $line['price_source_id'] ?? null,
            'pricing_context_hash' => $line['pricing_context_hash'] ?? null,
            'line_subtotal' => $line['line_subtotal'],
            'discount_calculation_type' => $this->enumValue($line['discount_calculation_type'] ?? null),
            'discount_rate' => $line['discount_rate'] ?? '0.000000',
            'discount_amount' => $line['discount_amount'],
            'tax_group_id' => $line['tax_group_id'],
            'tax_amount' => $line['non_withholding_tax_amount'],
            'withholding_amount' => $line['withholding_amount'],
            'charge_calculation_type' => $this->enumValue($line['charge_calculation_type'] ?? null),
            'charge_rate' => $line['charge_rate'] ?? '0.000000',
            'charge_amount' => $line['charge_amount'],
            'line_total' => $line['line_total'],
            'taxes' => $line['taxes'],
        ];
    }

    /**
     * @param  list<array{data: PurchaseHeaderAdjustmentData, amount: string}>  $adjustments
     * @return list<array<string, mixed>>
     */
    private function adjustmentPreview(array $adjustments): array
    {
        return array_map(static function (array $adjustment): array {
            /** @var PurchaseHeaderAdjustmentData $data */
            $data = $adjustment['data'];

            $accounting = $adjustment['accounting'] ?? [];

            return [
                'name' => $data->name,
                'adjustment_type' => $data->adjustmentType->value,
                'effect' => $data->effect->value,
                'calculation_type' => $data->calculationType->value,
                'calculation_base' => $data->calculationBase->value,
                'rate' => $data->rate,
                'amount' => $adjustment['amount'],
                'allocation_method' => $data->allocationMethod->value,
                'manual_allocations' => $data->manualAllocations,
                'finance_mapping' => [
                    'cost_treatment' => $accounting['cost_treatment'] ?? $data->costTreatment,
                    'tax_treatment' => $accounting['tax_treatment'] ?? $data->taxTreatment,
                    'mapping_source' => $accounting['mapping_source'] ?? $data->mappingSource,
                    'final_treatment' => $accounting['final_treatment'] ?? null,
                    'profile_key' => $accounting['profile_key'] ?? null,
                    'finance_posting_profile_id' => $accounting['finance_posting_profile_id'] ?? null,
                    'finance_account_id' => $accounting['finance_account_id'] ?? null,
                ],
            ];
        }, $adjustments);
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function documentPlan(array $resolved): array
    {
        $stockLines = array_values(array_filter($resolved['lines'], static fn (array $line): bool => (bool) $line['is_stock']));
        $nonStockLines = array_values(array_filter($resolved['lines'], static fn (array $line): bool => ! (bool) $line['is_stock']));

        return [
            'purchase_order' => [
                'will_create' => true,
                'line_keys' => array_map(static fn (array $line): ?string => $line['client_line_key'] ?? null, $resolved['lines']),
            ],
            'goods_receipt' => [
                'will_create' => (bool) $resolved['options']['receive_stock_now'] && $stockLines !== [],
                'source' => 'purchase_order',
                'line_keys' => array_map(static fn (array $line): ?string => $line['client_line_key'] ?? null, $stockLines),
            ],
            'supplier_invoice' => [
                'will_create' => (bool) $resolved['options']['create_supplier_invoice_now'],
                'sources' => [
                    'stock_lines' => $stockLines === [] ? null : 'goods_receipt_note',
                    'non_stock_lines' => $nonStockLines === [] ? null : 'purchase_order',
                ],
                'line_keys' => array_map(static fn (array $line): ?string => $line['client_line_key'] ?? null, $resolved['lines']),
            ],
            'supplier_payment' => [
                'will_create' => (bool) $resolved['options']['record_payment_now'],
                'allocation_target' => (bool) $resolved['options']['record_payment_now'] ? 'supplier_invoice' : null,
            ],
        ];
    }

    private function purchaseOrderRef(PurchaseOrder $purchaseOrder): array
    {
        return ['id' => (int) $purchaseOrder->getKey(), 'number' => (string) $purchaseOrder->purchase_order_number, 'status' => $this->enumValue($purchaseOrder->status), 'url' => '/purchase/orders/'.$purchaseOrder->getKey()];
    }

    private function goodsReceiptRef(GoodsReceiptNote $goodsReceipt): array
    {
        return ['id' => (int) $goodsReceipt->getKey(), 'number' => (string) $goodsReceipt->grn_number, 'status' => $this->enumValue($goodsReceipt->status), 'url' => '/purchase/goods-receipts/'.$goodsReceipt->getKey()];
    }

    private function invoiceRef(Invoice $invoice): array
    {
        return ['id' => (int) $invoice->getKey(), 'number' => (string) $invoice->invoice_number, 'status' => $this->enumValue($invoice->status), 'url' => '/invoices/'.$invoice->getKey()];
    }

    private function paymentRef(Payment $payment): array
    {
        return ['id' => (int) $payment->getKey(), 'number' => (string) $payment->payment_number, 'status' => $this->enumValue($payment->status), 'url' => '/payments/'.$payment->getKey()];
    }

    private function inventoryMovementRef(InventoryMovement $movement): array
    {
        return ['id' => (int) $movement->getKey(), 'number' => (string) $movement->movement_number, 'status' => $this->enumValue($movement->status), 'url' => '/inventory/movements/'.$movement->getKey()];
    }

    private function financePostingRef(PostingResultData $posting): array
    {
        return ['id' => $posting->journalId, 'number' => $posting->journalNumber, 'status' => $posting->status, 'url' => '/finance/journals/'.$posting->journalId, 'total_debit' => $posting->totalDebit, 'total_credit' => $posting->totalCredit];
    }

    /**
     * @param  list<string>  $fields
     */
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

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}

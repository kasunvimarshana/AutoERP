<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceAdjustment;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;

final class FastPurchasePostingCoordinator
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $financePostings,
        private readonly FastPurchaseDocumentBuilder $documents,
        private readonly PurchaseAcquisitionCostAllocator $costs,
        private readonly PurchaseAdjustmentAllocationService $adjustmentAllocations,
    ) {}

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    public function createDocuments(array $resolved): array
    {
        $purchaseOrder = $this->documents->createPurchaseOrder($resolved);
        $goodsReceipt = null;
        $invoice = null;
        $payment = null;
        $financePostings = [];

        if ((bool) $resolved['options']['receive_stock_now']) {
            $goodsReceipt = $this->documents->createGoodsReceipt($resolved, $purchaseOrder);
            $financePostings = array_merge($financePostings, $this->postInventoryFinance($resolved, $goodsReceipt));
        }

        if ((bool) $resolved['options']['create_supplier_invoice_now']) {
            $invoice = $this->documents->createSupplierInvoice($resolved, $purchaseOrder, $goodsReceipt);
            $financePostings = array_merge($financePostings, $this->postInvoiceFinance($resolved, $invoice));
        }

        if ((bool) $resolved['options']['record_payment_now']) {
            if (! $invoice instanceof Invoice) {
                throw new InvalidArgumentException('Supplier payment requires a supplier invoice.');
            }

            $payment = $this->documents->createSupplierPayment($resolved, $invoice);
            $financePostings = array_merge($financePostings, $this->postPaymentFinance($resolved, $payment));
        }

        return [
            'purchase_order' => $purchaseOrder,
            'goods_receipt' => $goodsReceipt,
            'supplier_invoice' => $invoice,
            'supplier_payment' => $payment,
            'finance_postings' => $financePostings,
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postInventoryFinance(array $resolved, GoodsReceiptNote $goodsReceipt): array
    {
        $amount = $this->costs->goodsReceiptStockValue($goodsReceipt);
        if ($this->math->isZero($amount)) {
            return [];
        }

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'goods_receipt_note',
                sourceId: (int) $goodsReceipt->getKey(),
                tenantId: (int) $resolved['tenant_id'],
                organizationUnitId: $resolved['organization_unit_id'],
                sourceModule: 'purchase',
                sourceNumber: (string) $goodsReceipt->grn_number,
                sourceDate: $goodsReceipt->received_date?->toDateString() ?? (string) $resolved['purchase_date'],
            ),
            postingDate: (string) $resolved['purchase_date'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            lines: [
                new FinancePostingLine(profileKey: 'inventory', debit: $amount, description: 'Inventory'),
                new FinancePostingLine(profileKey: 'payable', credit: $amount, description: 'Goods received payable'),
            ],
            description: 'Fast purchase stock receipt '.$goodsReceipt->grn_number,
            postingProfileCode: 'inventory_receipt',
        ), $resolved['current_user_id'])];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postInvoiceFinance(array $resolved, Invoice $invoice): array
    {
        $directTaxable = $resolved['summary']['non_stock_taxable_total'];
        $tax = $resolved['summary']['tax_total'];
        $withholding = $resolved['summary']['withholding_total'];
        $nonTaxAdjustments = $this->invoiceAdjustmentFinanceLines($invoice);
        if ($this->math->isZero($directTaxable) && $this->math->isZero($tax) && $this->math->isZero($withholding) && $nonTaxAdjustments === []) {
            return [];
        }

        $creditPayable = $this->math->sub($this->math->add($directTaxable, $tax), $withholding);
        $lines = [];
        if (! $this->math->isZero($directTaxable)) {
            $lines[] = new FinancePostingLine(profileKey: 'expense', debit: $directTaxable, description: 'Purchase expense');
        }
        if (! $this->math->isZero($tax)) {
            $lines[] = new FinancePostingLine(profileKey: 'tax_receivable', debit: $tax, description: 'Input tax');
        }
        if (! $this->math->isZero($creditPayable)) {
            $lines[] = new FinancePostingLine(profileKey: 'payable', credit: $creditPayable, description: 'Supplier payable');
        }
        if (! $this->math->isZero($withholding)) {
            $lines[] = new FinancePostingLine(profileKey: 'withholding_payable', credit: $withholding, description: 'Withholding payable');
        }
        $lines = array_merge($lines, $nonTaxAdjustments);

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'purchase_invoice',
                sourceId: (int) $invoice->getKey(),
                tenantId: (int) $invoice->tenant_id,
                organizationUnitId: $invoice->organization_unit_id,
                sourceModule: 'purchase',
                sourceNumber: (string) $invoice->invoice_number,
                sourceDate: $invoice->invoice_date?->toDateString() ?? (string) $resolved['purchase_date'],
            ),
            postingDate: (string) $resolved['purchase_date'],
            currencyId: $invoice->currency_id,
            exchangeRate: (string) $invoice->exchange_rate,
            lines: $lines,
            description: 'Fast purchase supplier invoice '.$invoice->invoice_number,
            postingProfileCode: 'purchase_invoice',
        ), $resolved['current_user_id'])];
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return list<PostingResultData>
     */
    private function postPaymentFinance(array $resolved, Payment $payment): array
    {
        $payment->loadMissing('lines');
        $lines = [
            new FinancePostingLine(
                profileKey: 'payable',
                debit: (string) $payment->total_amount,
                description: 'Supplier payable',
                sourceLineType: 'payment',
                sourceLineId: (int) $payment->getKey(),
            ),
        ];

        foreach ($payment->lines as $line) {
            $lines[] = new FinancePostingLine(
                profileKey: 'settlement',
                credit: (string) $line->amount,
                description: 'Fast purchase payment settlement',
                sourceLineType: 'payment_line',
                sourceLineId: (int) $line->getKey(),
                contextType: 'payment_method',
                contextId: (int) $line->payment_method_id,
            );
        }

        return [$this->financePostings->post(new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'payment_made',
                sourceId: (int) $payment->getKey(),
                tenantId: (int) $payment->tenant_id,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: 'payment',
                sourceNumber: (string) $payment->payment_number,
                sourceDate: $payment->payment_date?->toDateString() ?? (string) $resolved['purchase_date'],
            ),
            postingDate: (string) $resolved['purchase_date'],
            currencyId: $payment->currency_id,
            exchangeRate: (string) $payment->exchange_rate,
            lines: $lines,
            description: 'Fast purchase supplier payment '.$payment->payment_number,
            postingProfileCode: 'payment_made',
        ), $resolved['current_user_id'])];
    }

    /**
     * @return list<FinancePostingLine>
     */
    private function invoiceAdjustmentFinanceLines(Invoice $invoice): array
    {
        $invoice->loadMissing('adjustments');
        $lines = [];
        foreach ($invoice->adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustment
                || $adjustment->source_adjustment_type !== 'purchase_header_adjustment'
                || $adjustment->source_adjustment_id === null
            ) {
                continue;
            }

            $sourceAdjustment = PurchaseHeaderAdjustment::query()->find((int) $adjustment->source_adjustment_id);
            if (! $sourceAdjustment instanceof PurchaseHeaderAdjustment) {
                continue;
            }

            $type = $sourceAdjustment->adjustment_type instanceof PurchaseAdjustmentType
                ? $sourceAdjustment->adjustment_type
                : PurchaseAdjustmentType::from((string) $sourceAdjustment->adjustment_type);
            $amount = $this->math->normalize((string) $adjustment->amount);
            if ($this->math->isZero($amount)
                || in_array($type, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true)
            ) {
                continue;
            }

            $recognizedAtInvoice = $this->adjustmentAllocations->recognizedAtInvoiceForAdjustment($sourceAdjustment, $adjustment);
            $amount = $this->math->isZero($recognizedAtInvoice)
                ? $this->adjustmentAllocations->invoiceResidualAmount($sourceAdjustment, $amount)
                : $recognizedAtInvoice;
            if ($this->math->isZero($amount)) {
                continue;
            }

            $adjustmentLine = new FinancePostingLine(
                profileKey: 'adjustment',
                debit: $adjustment->effect->value === 'increase' ? $amount : '0.000000',
                credit: $adjustment->effect->value === 'increase' ? '0.000000' : $amount,
                description: (string) $adjustment->name,
                sourceLineType: 'purchase_header_adjustment',
                sourceLineId: (int) $sourceAdjustment->getKey(),
                contextType: 'purchase_adjustment',
                contextId: (int) $sourceAdjustment->getKey(),
            );
            $payableLine = new FinancePostingLine(
                profileKey: 'payable',
                debit: $adjustment->effect->value === 'increase' ? '0.000000' : $amount,
                credit: $adjustment->effect->value === 'increase' ? $amount : '0.000000',
                description: 'Supplier payable',
                sourceLineType: 'purchase_header_adjustment',
                sourceLineId: (int) $sourceAdjustment->getKey(),
            );

            $lines[] = $adjustmentLine;
            $lines[] = $payableLine;
        }

        return $lines;
    }

}
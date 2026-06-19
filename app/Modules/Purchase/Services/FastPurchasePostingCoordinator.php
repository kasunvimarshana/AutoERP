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
use Modules\Finance\Models\FinanceAccount;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\GoodsReceiptNote;

final class FastPurchasePostingCoordinator
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FinancePostingInterface $financePostings,
        private readonly FastPurchaseDocumentBuilder $documents,
        private readonly PurchaseAcquisitionCostAllocator $costs,
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
                new FinancePostingLine(null, 'Inventory', debit: $amount, profileKey: 'inventory'),
                new FinancePostingLine(null, 'Goods received payable', credit: $amount, profileKey: 'payable'),
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
        $nonTaxAdjustments = $this->nonTaxAdjustmentFinanceLines($resolved);
        if ($this->math->isZero($directTaxable) && $this->math->isZero($tax) && $this->math->isZero($withholding) && $nonTaxAdjustments === []) {
            return [];
        }

        $creditPayable = $this->math->sub($this->math->add($directTaxable, $tax), $withholding);
        $lines = [];
        if (! $this->math->isZero($directTaxable)) {
            $lines[] = new FinancePostingLine(null, 'Purchase expense', debit: $directTaxable, profileKey: 'expense');
        }
        if (! $this->math->isZero($tax)) {
            $lines[] = new FinancePostingLine(null, 'Input tax', debit: $tax, profileKey: 'tax_receivable');
        }
        if (! $this->math->isZero($creditPayable)) {
            $lines[] = new FinancePostingLine(null, 'Supplier payable', credit: $creditPayable, profileKey: 'payable');
        }
        if (! $this->math->isZero($withholding)) {
            $lines[] = new FinancePostingLine(null, 'Withholding payable', credit: $withholding, profileKey: 'payable');
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
        $lines = [
            new FinancePostingLine(null, 'Supplier payable', debit: (string) $payment->total_amount, profileKey: 'payable'),
        ];

        foreach ($resolved['payment']['source_accounts'] as $row) {
            /** @var FinanceAccount $account */
            $account = $row['account'];
            $lines[] = new FinancePostingLine(
                accountCode: (string) $account->code,
                accountName: (string) $account->name,
                credit: (string) $row['amount'],
                description: 'Fast purchase payment source',
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
     * @param  array<string, mixed>  $resolved
     * @return list<FinancePostingLine>
     */
    private function nonTaxAdjustmentFinanceLines(array $resolved): array
    {
        $lines = [];
        foreach ($resolved['adjustments'] as $adjustment) {
            /** @var PurchaseHeaderAdjustmentData $data */
            $data = $adjustment['data'];
            $amount = $adjustment['amount'];
            if ($this->math->isZero($amount)
                || in_array($data->adjustmentType, [PurchaseAdjustmentType::Tax, PurchaseAdjustmentType::Withholding], true)
            ) {
                continue;
            }

            if ($data->effect === PurchaseAdjustmentEffect::Increase) {
                $lines[] = new FinancePostingLine(null, $data->name, debit: $amount, profileKey: 'expense');
                $lines[] = new FinancePostingLine(null, 'Supplier payable', credit: $amount, profileKey: 'payable');
            } else {
                $lines[] = new FinancePostingLine(null, 'Supplier payable', debit: $amount, profileKey: 'payable');
                $lines[] = new FinancePostingLine(null, $data->name, credit: $amount, profileKey: 'expense');
            }
        }

        return $lines;
    }
}

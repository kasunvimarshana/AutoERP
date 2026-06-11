<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Sales\Models\SalesDelivery;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxPostingContext;
use Modules\Tax\Models\TaxDocumentSnapshot;
use Modules\Tax\Models\TaxTransaction;

final class TaxDocumentIntegrationService
{
    public function __construct(
        private readonly TaxCalculationService $calculator,
        private readonly TaxSnapshotService $snapshots,
        private readonly TaxPostingContextService $postingContexts,
        private readonly DecimalMath $math,
    ) {}

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function snapshotInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing('lines');

        $lineIds = [];
        $lines = [];
        foreach ($invoice->lines as $line) {
            $lineNumber = (int) ($line->line_number ?: count($lines) + 1);
            $lineIds[$lineNumber] = (int) $line->getKey();
            $lines[] = new TaxCalculationLineData(
                lineNumber: $lineNumber,
                quantity: (string) $line->quantity,
                unitPrice: (string) $line->unit_price,
                itemId: $line->item_id !== null ? (int) $line->item_id : null,
                discountBeforeTax: (string) $line->discount_amount,
                chargeAfterTax: (string) $line->charge_amount,
            );
        }

        $calculation = $this->calculator->calculate(new TaxCalculationData(
            tenantId: (int) $invoice->tenant_id,
            documentType: $this->invoiceDocumentType($invoice),
            documentDate: $invoice->invoice_date->toDateString(),
            organizationUnitId: $invoice->organization_unit_id,
            customerId: $this->partyId($invoice, 'customer'),
            supplierId: $this->partyId($invoice, 'supplier'),
            lines: $lines,
        ));

        return $this->snapshots->snapshotCalculation($calculation, [
            'tenant_id' => (int) $invoice->tenant_id,
            'organization_unit_id' => $invoice->organization_unit_id,
            'source_module' => 'invoice',
            'source_type' => 'invoice',
            'source_id' => (int) $invoice->getKey(),
            'source_number' => (string) $invoice->invoice_number,
            'source_date' => $invoice->invoice_date->toDateString(),
            'line_ids' => $lineIds,
        ]);
    }

    public function postInvoice(Invoice $invoice): void
    {
        $sourceType = 'invoice';
        $sourceId = (int) $invoice->getKey();
        $tenantId = (int) $invoice->tenant_id;

        if (! TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            $this->snapshotInvoice($invoice);
        }

        $snapshots = TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();

        foreach ($snapshots as $snapshot) {
            if (TaxTransaction::query()->where('tax_document_snapshot_id', $snapshot->getKey())->exists()) {
                continue;
            }

            $this->snapshots->recordTransaction($snapshot, [
                'transaction_date' => $invoice->posted_at?->toDateString() ?? $invoice->invoice_date->toDateString(),
                'party_type' => $invoice->party_type,
                'party_id' => $invoice->party_id,
            ]);
        }

        $this->snapshots->markPosted($tenantId, $sourceType, $sourceId);
    }

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function snapshotGoodsReceiptNote(GoodsReceiptNote $grn): array
    {
        $grn->loadMissing('lines');

        $lineIds = [];
        $lines = [];
        foreach ($grn->lines as $line) {
            $lineNumber = count($lines) + 1;
            $lineIds[$lineNumber] = (int) $line->getKey();
            $lines[] = new TaxCalculationLineData(
                lineNumber: $lineNumber,
                quantity: (string) $line->accepted_quantity,
                unitPrice: (string) $line->unit_price,
                itemId: $line->item_id !== null ? (int) $line->item_id : null,
                discountBeforeTax: (string) $line->discount_amount,
                chargeAfterTax: (string) $line->charge_amount,
            );
        }

        $calculation = $this->calculator->calculate(new TaxCalculationData(
            tenantId: (int) $grn->tenant_id,
            documentType: 'purchase_goods_receipt_note',
            documentDate: $grn->received_date->toDateString(),
            organizationUnitId: $grn->organization_unit_id,
            supplierId: $grn->supplier_id !== null ? (int) $grn->supplier_id : null,
            lines: $lines,
        ));

        return $this->snapshots->snapshotCalculation($calculation, [
            'tenant_id' => (int) $grn->tenant_id,
            'organization_unit_id' => $grn->organization_unit_id,
            'source_module' => 'purchase',
            'source_type' => 'goods_receipt_note',
            'source_id' => (int) $grn->getKey(),
            'source_number' => (string) $grn->grn_number,
            'source_date' => $grn->received_date->toDateString(),
            'line_ids' => $lineIds,
        ]);
    }

    public function postGoodsReceiptNote(GoodsReceiptNote $grn): void
    {
        $this->postSource(
            sourceType: 'goods_receipt_note',
            sourceId: (int) $grn->getKey(),
            tenantId: (int) $grn->tenant_id,
            snapshot: fn (): array => $this->snapshotGoodsReceiptNote($grn),
            attributes: [
                'transaction_date' => $grn->posted_at?->toDateString() ?? $grn->received_date->toDateString(),
                'party_type' => $grn->supplier_id !== null ? 'supplier' : null,
                'party_id' => $grn->supplier_id,
            ],
        );
    }

    /**
     * @return list<TaxDocumentSnapshot>
     */
    public function snapshotSalesDelivery(SalesDelivery $delivery): array
    {
        $delivery->loadMissing(['lines.salesOrderLine']);

        $lineIds = [];
        $lines = [];
        foreach ($delivery->lines as $line) {
            $lineNumber = count($lines) + 1;
            $lineIds[$lineNumber] = (int) $line->getKey();
            $lines[] = new TaxCalculationLineData(
                lineNumber: $lineNumber,
                quantity: (string) $line->delivered_quantity,
                unitPrice: (string) $line->unit_price,
                itemId: $line->item_id !== null ? (int) $line->item_id : null,
                discountBeforeTax: (string) ($line->salesOrderLine?->discount_amount ?? '0.000000'),
                chargeAfterTax: (string) ($line->salesOrderLine?->charge_amount ?? '0.000000'),
            );
        }

        $calculation = $this->calculator->calculate(new TaxCalculationData(
            tenantId: (int) $delivery->tenant_id,
            documentType: 'sales_delivery',
            documentDate: $delivery->delivery_date->toDateString(),
            organizationUnitId: $delivery->organization_unit_id,
            customerId: $delivery->customer_id !== null ? (int) $delivery->customer_id : null,
            lines: $lines,
        ));

        return $this->snapshots->snapshotCalculation($calculation, [
            'tenant_id' => (int) $delivery->tenant_id,
            'organization_unit_id' => $delivery->organization_unit_id,
            'source_module' => 'sales',
            'source_type' => 'sales_delivery',
            'source_id' => (int) $delivery->getKey(),
            'source_number' => (string) $delivery->delivery_number,
            'source_date' => $delivery->delivery_date->toDateString(),
            'line_ids' => $lineIds,
        ]);
    }

    public function postSalesDelivery(SalesDelivery $delivery): void
    {
        $this->postSource(
            sourceType: 'sales_delivery',
            sourceId: (int) $delivery->getKey(),
            tenantId: (int) $delivery->tenant_id,
            snapshot: fn (): array => $this->snapshotSalesDelivery($delivery),
            attributes: [
                'transaction_date' => $delivery->posted_at?->toDateString() ?? $delivery->delivery_date->toDateString(),
                'party_type' => $delivery->customer_id !== null ? 'customer' : null,
                'party_id' => $delivery->customer_id,
            ],
        );
    }

    public function withholdingPostingContextForInvoice(
        Invoice $invoice,
        string $postingDate,
        string $counterpartyAccountCode,
        string $counterpartyAccountName,
    ): TaxPostingContext {
        $tenantId = (int) $invoice->tenant_id;
        $sourceType = 'invoice';
        $sourceId = (int) $invoice->getKey();

        if (! TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            $this->snapshotInvoice($invoice);
        }

        $taxLines = TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('is_withholding', true)
            ->get()
            ->map(fn (TaxDocumentSnapshot $snapshot): TaxAmountData => $this->amountFromSnapshot($snapshot))
            ->values()
            ->all();

        return $this->postingContexts->build(
            source: new PostingSourceData(
                sourceType: $sourceType,
                sourceId: $sourceId,
                tenantId: $tenantId,
                organizationUnitId: $invoice->organization_unit_id,
                sourceModule: 'invoice',
                sourceNumber: (string) $invoice->invoice_number,
                sourceDate: $invoice->invoice_date->toDateString(),
            ),
            postingDate: $postingDate,
            taxLines: $taxLines,
            counterpartyAccountCode: $counterpartyAccountCode,
            counterpartyAccountName: $counterpartyAccountName,
            description: 'Withholding tax '.$invoice->invoice_number,
        );
    }

    public function withholdingPostingContextForPayment(
        Payment $payment,
        string $postingDate,
        string $counterpartyAccountCode,
        string $counterpartyAccountName,
    ): TaxPostingContext {
        $payment->loadMissing('allocations');
        $tenantId = (int) $payment->tenant_id;
        $taxLines = [];

        foreach ($payment->allocations as $allocation) {
            $status = $allocation->status instanceof \BackedEnum ? $allocation->status->value : (string) $allocation->status;
            if ($status !== 'active') {
                continue;
            }
            if ($this->math->isZero((string) $allocation->invoice_total)) {
                throw new InvalidArgumentException('Cannot calculate payment withholding tax against a zero invoice total.');
            }

            $invoice = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->where('organization_unit_id', $allocation->organization_unit_id)
                ->findOrFail((int) $allocation->invoice_id);

            if (! TaxDocumentSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('source_type', 'invoice')
                ->where('source_id', (int) $invoice->getKey())
                ->exists()) {
                $this->snapshotInvoice($invoice);
            }

            $ratio = $this->math->div((string) $allocation->allocated_amount, (string) $allocation->invoice_total, 12);
            foreach (TaxDocumentSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('source_type', 'invoice')
                ->where('source_id', (int) $invoice->getKey())
                ->where('is_withholding', true)
                ->get() as $snapshot) {
                $taxLines[] = $this->amountFromSnapshot($snapshot, $ratio);
            }
        }

        return $this->postingContexts->build(
            source: new PostingSourceData(
                sourceType: 'payment',
                sourceId: (int) $payment->getKey(),
                tenantId: $tenantId,
                organizationUnitId: $payment->organization_unit_id,
                sourceModule: 'payment',
                sourceNumber: (string) $payment->payment_number,
                sourceDate: $payment->payment_date->toDateString(),
            ),
            postingDate: $postingDate,
            taxLines: $taxLines,
            counterpartyAccountCode: $counterpartyAccountCode,
            counterpartyAccountName: $counterpartyAccountName,
            description: 'Withholding tax '.$payment->payment_number,
        );
    }

    /**
     * @param  callable(): list<TaxDocumentSnapshot>  $snapshot
     * @param  array<string, mixed>  $attributes
     */
    private function postSource(
        string $sourceType,
        int $sourceId,
        int $tenantId,
        callable $snapshot,
        array $attributes,
    ): void {
        if (! TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists()) {
            $snapshot();
        }

        $snapshots = TaxDocumentSnapshot::query()
            ->where('tenant_id', $tenantId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();

        foreach ($snapshots as $row) {
            if (TaxTransaction::query()->where('tax_document_snapshot_id', $row->getKey())->exists()) {
                continue;
            }

            $this->snapshots->recordTransaction($row, $attributes);
        }

        $this->snapshots->markPosted($tenantId, $sourceType, $sourceId);
    }

    private function amountFromSnapshot(TaxDocumentSnapshot $snapshot, ?string $ratio = null): TaxAmountData
    {
        if ($snapshot->tax_id === null) {
            throw new InvalidArgumentException("Tax snapshot [{$snapshot->getKey()}] is missing a tax master reference.");
        }

        $taxableAmount = (string) $snapshot->taxable_amount;
        $taxAmount = (string) $snapshot->tax_amount;
        $totalAmount = (string) $snapshot->total_amount;
        if ($ratio !== null) {
            $taxableAmount = $this->math->mul($taxableAmount, $ratio);
            $taxAmount = $this->math->mul($taxAmount, $ratio);
            $totalAmount = $this->math->mul($totalAmount, $ratio);
        }

        return new TaxAmountData(
            taxId: (int) $snapshot->tax_id,
            taxCode: (string) $snapshot->tax_code,
            taxName: (string) $snapshot->tax_name,
            taxType: (string) $snapshot->tax_type,
            calculationMethod: (string) $snapshot->calculation_method,
            rate: (string) $snapshot->rate,
            sequence: (int) $snapshot->sequence,
            taxableAmount: $taxableAmount,
            taxAmount: $taxAmount,
            totalAfterTax: $totalAmount,
            isWithholding: (bool) $snapshot->is_withholding,
            recoverable: (bool) $snapshot->recoverable,
            payable: (bool) $snapshot->payable,
            receivable: (bool) $snapshot->receivable,
        );
    }

    private function invoiceDocumentType(Invoice $invoice): string
    {
        $direction = $invoice->direction instanceof \BackedEnum ? $invoice->direction->value : (string) $invoice->direction;
        $type = $invoice->invoice_type instanceof \BackedEnum ? $invoice->invoice_type->value : (string) $invoice->invoice_type;

        return 'invoice_'.$direction.'_'.$type;
    }

    private function partyId(Invoice $invoice, string $partyType): ?int
    {
        return $invoice->party_type === $partyType && $invoice->party_id !== null
            ? (int) $invoice->party_id
            : null;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Tax\Services;

use Modules\Invoice\Models\Invoice;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;
use Modules\Tax\Models\TaxDocumentSnapshot;
use Modules\Tax\Models\TaxTransaction;

final class TaxDocumentIntegrationService
{
    public function __construct(
        private readonly TaxCalculationService $calculator,
        private readonly TaxSnapshotService $snapshots,
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

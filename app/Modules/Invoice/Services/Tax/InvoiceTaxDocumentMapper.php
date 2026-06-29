<?php

declare(strict_types=1);

namespace Modules\Invoice\Services\Tax;

use BackedEnum;
use Modules\Invoice\Constants\InvoiceTaxMetadata;
use Modules\Invoice\Models\Invoice;
use Modules\Tax\Data\TaxableDocumentData;
use Modules\Tax\Data\TaxableDocumentLineData;

final class InvoiceTaxDocumentMapper
{
    public function map(Invoice $invoice): TaxableDocumentData
    {
        $invoice->loadMissing('lines');
        $lines = [];
        foreach ($invoice->lines as $line) {
            $lineNumber = (int) ($line->line_number ?: count($lines) + 1);
            $lines[] = new TaxableDocumentLineData(
                lineId: (int) $line->getKey(),
                lineNumber: $lineNumber,
                quantity: (string) $line->quantity,
                unitPrice: (string) $line->unit_price,
                itemId: $line->item_id === null ? null : (int) $line->item_id,
                taxGroupId: is_array($line->metadata) && is_numeric($line->metadata[InvoiceTaxMetadata::TAX_GROUP_ID] ?? null)
                    ? (int) $line->metadata[InvoiceTaxMetadata::TAX_GROUP_ID]
                    : null,
                discountBeforeTax: (string) $line->discount_amount,
                chargeAfterTax: (string) $line->charge_amount,
            );
        }

        $direction = $invoice->direction instanceof BackedEnum ? $invoice->direction->value : (string) $invoice->direction;
        $type = $invoice->invoice_type instanceof BackedEnum ? $invoice->invoice_type->value : (string) $invoice->invoice_type;
        $partyType = $invoice->party_type === null ? null : (string) $invoice->party_type;

        return new TaxableDocumentData(
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id === null ? null : (int) $invoice->organization_unit_id,
            documentType: 'invoice_'.$direction.'_'.$type,
            sourceModule: 'invoice',
            sourceType: 'invoice',
            sourceId: (int) $invoice->getKey(),
            sourceNumber: (string) $invoice->invoice_number,
            sourceDate: $invoice->invoice_date->toDateString(),
            transactionDate: $invoice->posted_at?->toDateString() ?? $invoice->invoice_date->toDateString(),
            partyType: $partyType,
            partyId: $invoice->party_id === null ? null : (int) $invoice->party_id,
            lines: $lines,
        );
    }
}

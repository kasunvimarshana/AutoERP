<?php

declare(strict_types=1);

namespace Modules\Purchase\Services\Tax;

use Modules\Purchase\Models\PurchaseReturn;
use Modules\Tax\Data\TaxReturnDocumentData;
use Modules\Tax\Data\TaxReturnLineData;

final class PurchaseReturnTaxDocumentMapper
{
    public function map(PurchaseReturn $return, ?int $debitNoteId = null): TaxReturnDocumentData
    {
        $return->loadMissing('lines');

        return new TaxReturnDocumentData(
            tenantId: (int) $return->tenant_id,
            organizationUnitId: $return->organization_unit_id === null ? null : (int) $return->organization_unit_id,
            sourceModule: 'purchase',
            sourceType: 'purchase_return',
            sourceId: (int) $return->getKey(),
            sourceNumber: (string) $return->return_number,
            sourceDate: $return->return_date->toDateString(),
            partyType: $return->supplier_id === null ? null : 'supplier',
            partyId: $return->supplier_id === null ? null : (int) $return->supplier_id,
            lines: $return->lines->map(static fn ($line): TaxReturnLineData => new TaxReturnLineData(
                returnLineId: (int) $line->getKey(),
                sourceLineType: (string) $line->source_line_type,
                sourceLineId: $line->source_line_id === null ? null : (int) $line->source_line_id,
                returnedQuantity: (string) $line->returned_quantity,
                sourceQuantity: (string) $line->source_quantity,
            ))->values()->all(),
            noteMetadata: array_filter(['debit_note_id' => $debitNoteId], static fn ($value): bool => $value !== null),
        );
    }
}

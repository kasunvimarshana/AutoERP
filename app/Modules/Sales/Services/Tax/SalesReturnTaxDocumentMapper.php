<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Tax;

use Modules\Sales\Models\SalesReturn;
use Modules\Tax\Data\TaxReturnDocumentData;
use Modules\Tax\Data\TaxReturnLineData;

final class SalesReturnTaxDocumentMapper
{
    public function map(SalesReturn $return, ?int $creditNoteId = null): TaxReturnDocumentData
    {
        $return->loadMissing('lines');

        return new TaxReturnDocumentData(
            tenantId: (int) $return->tenant_id,
            organizationUnitId: $return->organization_unit_id === null ? null : (int) $return->organization_unit_id,
            sourceModule: 'sales',
            sourceType: 'sales_return',
            sourceId: (int) $return->getKey(),
            sourceNumber: (string) $return->return_number,
            sourceDate: $return->return_date->toDateString(),
            partyType: $return->customer_id === null ? null : 'customer',
            partyId: $return->customer_id === null ? null : (int) $return->customer_id,
            lines: $return->lines->map(static fn ($line): TaxReturnLineData => new TaxReturnLineData(
                returnLineId: (int) $line->getKey(),
                sourceLineType: (string) $line->source_line_type,
                sourceLineId: $line->source_line_id === null ? null : (int) $line->source_line_id,
                returnedQuantity: (string) $line->returned_quantity,
                sourceQuantity: (string) $line->source_quantity,
            ))->values()->all(),
            noteMetadata: array_filter(['credit_note_id' => $creditNoteId], static fn ($value): bool => $value !== null),
        );
    }
}

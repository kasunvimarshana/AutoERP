<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\Data\InvoiceSourceCancellationContext;
use Modules\Invoice\Data\InvoiceSourceLineSnapshot;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSourceLine;

final class InvoiceSourceRestorationService
{
    public function __construct(
        private readonly InvoiceSourceCancellationRegistry $handlers,
    ) {}

    public function restore(Invoice $invoice): void
    {
        $invoice->loadMissing('sourceLines');
        $this->handlers->restore(new InvoiceSourceCancellationContext(
            invoiceId: (int) $invoice->getKey(),
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id === null
                ? null
                : (int) $invoice->organization_unit_id,
            sourceLines: $invoice->sourceLines
                ->map(static fn (InvoiceSourceLine $sourceLine): InvoiceSourceLineSnapshot => new InvoiceSourceLineSnapshot(
                    sourceType: (string) $sourceLine->source_type,
                    sourceId: (int) $sourceLine->source_id,
                    sourceLineType: (string) $sourceLine->source_line_type,
                    sourceLineId: (int) $sourceLine->source_line_id,
                    invoicedQuantity: (string) $sourceLine->invoiced_quantity,
                ))
                ->values()
                ->all(),
        ));
    }
}

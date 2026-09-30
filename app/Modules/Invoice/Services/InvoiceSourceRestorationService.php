<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Invoice\Data\InvoiceSourceLineSnapshot;
use Modules\Invoice\Data\InvoiceSourceRestorationContext;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceSourceLine;

final class InvoiceSourceRestorationService
{
    public function __construct(private readonly InvoiceSourceRestorationRegistry $handlers) {}

    public function restore(Invoice $invoice, InvoiceStatus $terminalStatus, ?int $actorId = null, ?string $reason = null): void
    {
        $invoice->loadMissing('sourceLines');
        $this->handlers->restore(new InvoiceSourceRestorationContext(
            invoiceId: (int) $invoice->getKey(),
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id === null
                ? null
                : (int) $invoice->organization_unit_id,
            terminalStatus: $terminalStatus,
            actorId: $actorId,
            reason: $reason,
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

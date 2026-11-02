<?php

declare(strict_types=1);

namespace Modules\Invoice\Data;

final readonly class InvoiceSourceCancellationContext
{
    /**
     * @param list<InvoiceSourceLineSnapshot> $sourceLines
     */
    public function __construct(
        public int $invoiceId,
        public int $tenantId,
        public ?int $organizationUnitId,
        public array $sourceLines,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

final readonly class InvoiceSourceLineData
{
    public function __construct(
        public int $tenantId,
        public string $sourceType,
        public int $sourceId,
        public string $sourceLineType,
        public int $sourceLineId,
        public string $sourceQuantity,
        public string $invoicedQuantity,
        public string $sourceUnitPrice,
        public string $sourceLineTotal,
        public ?int $organizationUnitId = null,
        public string $previouslyInvoicedQuantity = '0.000000',
        public ?string $invoicedLineTotal = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

final readonly class InvoiceSourceData
{
    public function __construct(
        public int $tenantId,
        public string $sourceType,
        public int $sourceId,
        public ?int $organizationUnitId = null,
        public ?string $sourceDocumentNumber = null,
        public ?string $sourceDocumentDate = null,
        public string $sourceSubtotal = '0.000000',
        public string $sourceAdjustmentTotal = '0.000000',
        public string $sourceGrandTotal = '0.000000',
        public string $invoicedAmount = '0.000000',
        public string $allocatedAdjustmentAmount = '0.000000',
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceDirection;

final readonly class ManualInvoiceData
{
    /**
     * @param  list<ManualInvoiceLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public InvoiceDirection $direction,
        public string $invoiceDate,
        public int $organizationUnitId,
        public ?int $customerId = null,
        public ?int $supplierId = null,
        public ?string $dueDate = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?int $documentTaxGroupId = null,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
    ) {}
}

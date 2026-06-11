<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class CreateSalesInvoiceData
{
    /**
     * @param  list<SalesInvoiceSourceData>  $sources
     */
    public function __construct(
        public int $tenantId,
        public string $invoiceDate,
        public ?int $organizationUnitId = null,
        public ?string $invoiceNumber = null,
        public ?int $customerId = null,
        public ?string $dueDate = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $sources = [],
    ) {}
}

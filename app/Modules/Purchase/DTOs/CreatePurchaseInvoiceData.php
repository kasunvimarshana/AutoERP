<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

/**
 * @param  list<PurchaseInvoiceSourceData>  $sources
 */
final readonly class CreatePurchaseInvoiceData
{
    public function __construct(
        public int $tenantId,
        public string $invoiceDate,
        public ?int $organizationUnitId = null,
        public ?string $invoiceNumber = null,
        public ?string $supplierType = null,
        public ?int $supplierId = null,
        public ?string $dueDate = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $sources = [],
    ) {}
}

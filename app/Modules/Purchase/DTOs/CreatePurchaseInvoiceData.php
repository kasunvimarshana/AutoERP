<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\InvoiceStatus;

/**
 * @param  list<PurchaseInvoiceSourceData>  $sources
 * @param  list<InvoiceLineData>  $directLines
 * @param  list<InvoiceAdjustmentData>  $adjustments
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
        public InvoiceStatus $status = InvoiceStatus::Draft,
        public array $directLines = [],
        public array $adjustments = [],
    ) {}
}

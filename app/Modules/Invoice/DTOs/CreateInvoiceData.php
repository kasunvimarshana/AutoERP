<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Tax\DTOs\TaxCalculationResult;

final readonly class CreateInvoiceData
{
    /**
     * @param  list<InvoiceLineData>  $lines
     * @param  list<InvoiceSourceData>  $sources
     * @param  list<InvoiceSourceLineData>  $sourceLines
     * @param  list<InvoiceAdjustmentData>  $adjustments
     */
    public function __construct(
        public int $tenantId,
        public InvoiceType $invoiceType,
        public InvoiceDirection $direction,
        public string $invoiceDate,
        public ?int $organizationUnitId = null,
        public ?string $invoiceNumber = null,
        public ?string $partyType = null,
        public ?int $partyId = null,
        public ?string $dueDate = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public InvoiceStatus $status = InvoiceStatus::Draft,
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
        public array $sources = [],
        public array $sourceLines = [],
        public array $adjustments = [],
        public ?TaxCalculationResult $taxCalculation = null,
        public ?InvoicePostingPlanData $postingPlan = null,
        public ?string $supplyDate = null,
        public ?string $supplyPeriodStart = null,
        public ?string $supplyPeriodEnd = null,
        public ?string $placeOfSupply = null,
        public ?string $paymentMode = null,
        public ?string $paymentTerms = null,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class CreateSalesQuotationData
{
    /**
     * @param  list<SalesLineData>  $lines
     * @param  list<SalesHeaderAdjustmentData>  $adjustments
     */
    public function __construct(
        public int $tenantId,
        public string $quotationDate,
        public int $customerId,
        public ?int $organizationUnitId = null,
        public ?string $quotationNumber = null,
        public ?string $validUntil = null,
        public ?int $currencyId = null,
        public string $exchangeRate = '1.000000',
        public ?string $notes = null,
        public ?int $createdBy = null,
        public array $lines = [],
        public array $adjustments = [],
    ) {}
}

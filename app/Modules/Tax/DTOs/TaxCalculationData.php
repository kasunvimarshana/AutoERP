<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxCalculationData
{
    /**
     * @param  list<TaxCalculationLineData>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $documentType,
        public string $documentDate,
        public ?int $organizationUnitId = null,
        public ?int $customerId = null,
        public ?int $supplierId = null,
        public ?int $documentTaxGroupId = null,
        public array $lines = [],
        public ?int $headerTaxGroupId = null,
        public string $headerDiscountBeforeTax = '0.000000',
        public string $headerDiscountAfterTax = '0.000000',
        public string $headerChargeBeforeTax = '0.000000',
        public string $headerChargeAfterTax = '0.000000',
    ) {}
}

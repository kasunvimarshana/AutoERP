<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxCalculationLineData
{
    /**
     * @param  list<ApplicableTaxData>  $applicableTaxes
     */
    public function __construct(
        public int $lineNumber,
        public string $quantity = '1.000000',
        public string $unitPrice = '0.000000',
        public ?int $itemId = null,
        public ?int $taxGroupId = null,
        public string $discountBeforeTax = '0.000000',
        public string $discountAfterTax = '0.000000',
        public string $chargeBeforeTax = '0.000000',
        public string $chargeAfterTax = '0.000000',
        public ?string $taxableAmount = null,
        public array $applicableTaxes = [],
    ) {}
}

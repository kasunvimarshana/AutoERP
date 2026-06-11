<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxCalculationResult
{
    /**
     * @param  list<TaxLineCalculationResult>  $lineResults
     * @param  list<TaxAmountData>  $headerTaxes
     */
    public function __construct(
        public string $taxableAmount,
        public string $taxAmount,
        public string $withholdingAmount,
        public string $totalAmount,
        public string $lineTaxAmount,
        public string $headerTaxAmount,
        public array $lineResults,
        public array $headerTaxes = [],
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesCalculationResult
{
    /**
     * @param  list<string>  $lineTotals
     */
    public function __construct(
        public string $subtotal,
        public string $lineDiscountTotal,
        public string $lineTaxTotal,
        public string $lineChargeTotal,
        public string $headerIncreaseTotal,
        public string $headerDecreaseTotal,
        public string $grandTotal,
        public array $lineTotals = [],
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchaseCalculationResult
{
    /**
     * @param  list<string>  $lineTotals
     */
    public function __construct(
        public string $subtotal,
        public string $discountTotal,
        public string $taxTotal,
        public string $chargeTotal,
        public string $adjustmentTotal,
        public string $grandTotal,
        public array $lineTotals = [],
        public string $headerIncreaseTotal = '0.000000',
        public string $headerDecreaseTotal = '0.000000',
    ) {}
}

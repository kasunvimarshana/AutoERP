<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

final readonly class InvoiceCalculationResult
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
    ) {}
}

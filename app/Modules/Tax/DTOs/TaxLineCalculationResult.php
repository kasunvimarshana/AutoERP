<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxLineCalculationResult
{
    /**
     * @param  list<TaxAmountData>  $taxes
     */
    public function __construct(
        public int $lineNumber,
        public string $taxableAmount,
        public string $taxAmount,
        public string $withholdingAmount,
        public string $totalAmount,
        public array $taxes,
    ) {}
}

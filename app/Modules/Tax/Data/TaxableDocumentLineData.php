<?php

declare(strict_types=1);

namespace Modules\Tax\Data;

final readonly class TaxableDocumentLineData
{
    public function __construct(
        public int $lineId,
        public int $lineNumber,
        public string $quantity,
        public string $unitPrice,
        public ?int $itemId = null,
        public ?int $taxGroupId = null,
        public string $discountBeforeTax = '0.000000',
        public string $chargeAfterTax = '0.000000',
    ) {}
}

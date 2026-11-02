<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class TaxAmountData
{
    public function __construct(
        public int $taxId,
        public string $taxCode,
        public string $taxName,
        public string $taxType,
        public string $calculationMethod,
        public string $rate,
        public int $sequence,
        public string $taxableAmount,
        public string $taxAmount,
        public string $totalAfterTax,
        public bool $isWithholding = false,
        public bool $recoverable = false,
        public bool $payable = false,
        public bool $receivable = false,
    ) {}
}

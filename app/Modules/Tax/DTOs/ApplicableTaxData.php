<?php

declare(strict_types=1);

namespace Modules\Tax\DTOs;

final readonly class ApplicableTaxData
{
    public function __construct(
        public int $taxId,
        public string $taxCode,
        public string $taxName,
        public string $taxType,
        public string $calculationMethod,
        public string $rate,
        public int $sequence,
        public bool $isWithholding = false,
        public bool $recoverable = false,
        public bool $payable = false,
        public bool $receivable = false,
        public ?int $taxGroupId = null,
    ) {}
}

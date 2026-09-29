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

    /** @return array<string, int|string|bool> */
    public function toArray(): array
    {
        return [
            'tax_id' => $this->taxId,
            'tax_code' => $this->taxCode,
            'tax_name' => $this->taxName,
            'tax_type' => $this->taxType,
            'calculation_method' => $this->calculationMethod,
            'rate' => $this->rate,
            'sequence' => $this->sequence,
            'taxable_amount' => $this->taxableAmount,
            'tax_amount' => $this->taxAmount,
            'total_after_tax' => $this->totalAfterTax,
            'is_withholding' => $this->isWithholding,
            'recoverable' => $this->recoverable,
            'payable' => $this->payable,
            'receivable' => $this->receivable,
        ];
    }
}

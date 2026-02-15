<?php

declare(strict_types=1);

namespace App\Core\DTOs\Taxation;

use App\Core\DTOs\BaseDTO;

class TaxCalculationResultDTO extends BaseDTO
{
    public function __construct(
        public readonly float $baseAmount,
        public readonly float $taxAmount,
        public readonly float $totalAmount,
        public readonly bool $isInclusive,
        public readonly array $taxBreakdown,
        public readonly array $appliedTaxes,
        public readonly array $exemptionsApplied,
        public readonly ?int $jurisdictionId = null,
        public readonly string $calculationMethod = 'standard',
    ) {}

    public function toArray(): array
    {
        return [
            'base_amount' => $this->baseAmount,
            'tax_amount' => $this->taxAmount,
            'total_amount' => $this->totalAmount,
            'is_inclusive' => $this->isInclusive,
            'tax_breakdown' => $this->taxBreakdown,
            'applied_taxes' => $this->appliedTaxes,
            'exemptions_applied' => $this->exemptionsApplied,
            'jurisdiction_id' => $this->jurisdictionId,
            'calculation_method' => $this->calculationMethod,
        ];
    }
}

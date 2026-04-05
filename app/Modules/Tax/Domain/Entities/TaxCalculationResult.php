<?php
declare(strict_types=1);
namespace Modules\Tax\Domain\Entities;

/** Immutable result of a tax calculation for one TaxGroup on one base amount. */
class TaxCalculationResult
{
    public function __construct(
        private readonly int $taxGroupId,
        private readonly string $taxGroupCode,
        private readonly float $baseAmount,
        private readonly float $totalTax,
        private readonly array $breakdown, // [['code'=>string,'name'=>string,'rate'=>float,'tax'=>float], ...]
    ) {}

    public function getTaxGroupId(): int { return $this->taxGroupId; }
    public function getTaxGroupCode(): string { return $this->taxGroupCode; }
    public function getBaseAmount(): float { return $this->baseAmount; }
    public function getTotalTax(): float { return $this->totalTax; }
    public function getBreakdown(): array { return $this->breakdown; }
    public function getAmountWithTax(): float { return $this->baseAmount + $this->totalTax; }
}

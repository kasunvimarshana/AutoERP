<?php
declare(strict_types=1);
namespace Modules\Tax\Domain\Entities;

/**
 * A single tax rate entry belonging to a TaxGroup.
 * order: processing order for compound calculation.
 * isCompound: if true, this rate is applied on top of already-accumulated tax.
 */
class TaxGroupRate
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $taxGroupId,
        private string $taxRateCode,
        private string $taxRateName,
        private float $rate,        // percentage, e.g. 10 for 10%
        private int $sortOrder,
        private bool $isCompound,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getTaxGroupId(): int { return $this->taxGroupId; }
    public function getTaxRateCode(): string { return $this->taxRateCode; }
    public function getTaxRateName(): string { return $this->taxRateName; }
    public function getRate(): float { return $this->rate; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isCompound(): bool { return $this->isCompound; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    /** Calculate tax amount for a base amount (without compounding). */
    public function calculate(float $baseAmount): float
    {
        return $baseAmount * ($this->rate / 100);
    }

    /** Calculate tax on base + already accumulated tax (compound). */
    public function calculateCompound(float $baseAmount, float $accumulatedTax): float
    {
        return ($baseAmount + $accumulatedTax) * ($this->rate / 100);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class TaxRate
{
    private ?int $id;
    private int $tenantId;
    private string $name;
    private string $code;
    private string $taxType;
    private string $calculationMethod;
    private float $rate;
    private ?string $jurisdiction;
    private bool $isActive;
    private ?string $description;
    private ?\DateTimeInterface $effectiveFrom;
    private ?\DateTimeInterface $effectiveTo;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $code,
        string $taxType,
        float $rate,
        string $calculationMethod = 'exclusive',
        ?string $jurisdiction = null,
        bool $isActive = true,
        ?string $description = null,
        ?\DateTimeInterface $effectiveFrom = null,
        ?\DateTimeInterface $effectiveTo = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->code = $code;
        $this->taxType = $taxType;
        $this->calculationMethod = $calculationMethod;
        $this->rate = $rate;
        $this->jurisdiction = $jurisdiction;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->effectiveFrom = $effectiveFrom;
        $this->effectiveTo = $effectiveTo;
        $this->metadata = $metadata ?? new Metadata([]);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getTaxType(): string { return $this->taxType; }
    public function getCalculationMethod(): string { return $this->calculationMethod; }
    public function getRate(): float { return $this->rate; }
    public function getJurisdiction(): ?string { return $this->jurisdiction; }
    public function isActive(): bool { return $this->isActive; }
    public function getDescription(): ?string { return $this->description; }
    public function getEffectiveFrom(): ?\DateTimeInterface { return $this->effectiveFrom; }
    public function getEffectiveTo(): ?\DateTimeInterface { return $this->effectiveTo; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isEffective(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTimeImmutable;

        if ($this->effectiveFrom !== null && $now < $this->effectiveFrom) {
            return false;
        }

        if ($this->effectiveTo !== null && $now > $this->effectiveTo) {
            return false;
        }

        return true;
    }

    public function updateDetails(
        ?string $name = null,
        ?string $code = null,
        ?string $taxType = null,
        ?string $calculationMethod = null,
        ?float $rate = null,
        ?string $jurisdiction = null,
        ?bool $isActive = null,
        ?string $description = null,
        ?\DateTimeInterface $effectiveFrom = null,
        ?\DateTimeInterface $effectiveTo = null,
        ?Metadata $metadata = null,
    ): void {
        if ($name !== null) { $this->name = $name; }
        if ($code !== null) { $this->code = $code; }
        if ($taxType !== null) { $this->taxType = $taxType; }
        if ($calculationMethod !== null) { $this->calculationMethod = $calculationMethod; }
        if ($rate !== null) { $this->rate = $rate; }
        if ($jurisdiction !== null) { $this->jurisdiction = $jurisdiction; }
        if ($isActive !== null) { $this->isActive = $isActive; }
        if ($description !== null) { $this->description = $description; }
        if ($effectiveFrom !== null) { $this->effectiveFrom = $effectiveFrom; }
        if ($effectiveTo !== null) { $this->effectiveTo = $effectiveTo; }
        if ($metadata !== null) { $this->metadata = $metadata; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}

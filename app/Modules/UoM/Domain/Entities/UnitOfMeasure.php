<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Entities;

class UnitOfMeasure
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $categoryId,
        private string $name,
        private string $symbol,
        private bool $isBase,
        private float $conversionFactor,  // ratio to base unit
        private string $type,             // base|purchase|sales|inventory
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getName(): string { return $this->name; }
    public function getSymbol(): string { return $this->symbol; }
    public function isBase(): bool { return $this->isBase; }
    public function getConversionFactor(): float { return $this->conversionFactor; }
    public function getType(): string { return $this->type; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function convertToBase(float $quantity): float
    {
        return $quantity * $this->conversionFactor;
    }

    public function convertFromBase(float $baseQuantity): float
    {
        return $this->conversionFactor > 0 ? $baseQuantity / $this->conversionFactor : 0;
    }
}

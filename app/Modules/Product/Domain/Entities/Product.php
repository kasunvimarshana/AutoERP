<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Modules\Product\Domain\ValueObjects\ProductType;

class Product
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private ?int $categoryId,
        private string $name,
        private string $slug,
        private string $sku,
        private ProductType $type,
        private ?string $description,
        private string $status,       // active|inactive|discontinued
        private float $basePrice,
        private float $taxRate,
        private ?float $weight,
        private string $unit,
        private bool $isTrackable,
        private bool $isSerialized,
        private bool $isBatchTracked,
        private ?float $minStockLevel,
        private ?float $reorderPoint,
        private ?array $metadata,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCategoryId(): ?int { return $this->categoryId; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getSku(): string { return $this->sku; }
    public function getType(): ProductType { return $this->type; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function getBasePrice(): float { return $this->basePrice; }
    public function getTaxRate(): float { return $this->taxRate; }
    public function getWeight(): ?float { return $this->weight; }
    public function getUnit(): string { return $this->unit; }
    public function isTrackable(): bool { return $this->isTrackable; }
    public function isSerialized(): bool { return $this->isSerialized; }
    public function isBatchTracked(): bool { return $this->isBatchTracked; }
    public function getMinStockLevel(): ?float { return $this->minStockLevel; }
    public function getReorderPoint(): ?float { return $this->reorderPoint; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function activate(): void { $this->status = 'active'; }
    public function deactivate(): void { $this->status = 'inactive'; }
    public function discontinue(): void { $this->status = 'discontinued'; }
    public function updatePrice(float $price): void { $this->basePrice = $price; }
}

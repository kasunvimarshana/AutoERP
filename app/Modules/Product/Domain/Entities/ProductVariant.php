<?php
declare(strict_types=1);
namespace Modules\Product\Domain\Entities;

/**
 * Represents a specific variant of a variable product.
 *
 * Example: "T-Shirt" (variable) → variants: {"size":"L","colour":"blue"}, {"size":"M","colour":"red"}
 */
class ProductVariant
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $productId,
        private string $sku,
        private array $attributes,      // e.g. ['size' => 'L', 'colour' => 'blue']
        private ?float $priceOverride,
        private ?float $costOverride,
        private string $status,         // active | inactive
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getSku(): string { return $this->sku; }
    public function getAttributes(): array { return $this->attributes; }
    public function getPriceOverride(): ?float { return $this->priceOverride; }
    public function getCostOverride(): ?float { return $this->costOverride; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function isActive(): bool { return $this->status === 'active'; }
    public function activate(): void { $this->status = 'active'; }
    public function deactivate(): void { $this->status = 'inactive'; }

    public function updatePrice(float $price): void { $this->priceOverride = $price; }
}

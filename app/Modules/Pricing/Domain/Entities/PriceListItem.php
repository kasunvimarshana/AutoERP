<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class PriceListItem
{
    private ?int $id;
    private int $tenantId;
    private int $priceListId;
    private int $productId;
    private ?int $variationId;
    private float $unitPrice;
    private float $minQuantity;
    private ?float $maxQuantity;
    private float $discountPercent;
    private float $markupPercent;
    private string $currencyCode;
    private ?string $uomCode;
    private bool $isActive;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $priceListId,
        int $productId,
        float $unitPrice,
        float $minQuantity = 1.0,
        string $currencyCode = 'USD',
        ?int $variationId = null,
        ?float $maxQuantity = null,
        float $discountPercent = 0.0,
        float $markupPercent = 0.0,
        ?string $uomCode = null,
        bool $isActive = true,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->priceListId     = $priceListId;
        $this->productId       = $productId;
        $this->variationId     = $variationId;
        $this->unitPrice       = $unitPrice;
        $this->minQuantity     = $minQuantity;
        $this->maxQuantity     = $maxQuantity;
        $this->discountPercent = $discountPercent;
        $this->markupPercent   = $markupPercent;
        $this->currencyCode    = $currencyCode;
        $this->uomCode         = $uomCode;
        $this->isActive        = $isActive;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getPriceListId(): int { return $this->priceListId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getMinQuantity(): float { return $this->minQuantity; }
    public function getMaxQuantity(): ?float { return $this->maxQuantity; }
    public function getDiscountPercent(): float { return $this->discountPercent; }
    public function getMarkupPercent(): float { return $this->markupPercent; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function getUomCode(): ?string { return $this->uomCode; }
    public function isActive(): bool { return $this->isActive; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        int $productId,
        float $unitPrice,
        float $minQuantity,
        string $currencyCode,
        ?int $variationId,
        ?float $maxQuantity,
        float $discountPercent,
        float $markupPercent,
        ?string $uomCode,
        bool $isActive,
        ?Metadata $metadata,
    ): void {
        $this->productId       = $productId;
        $this->variationId     = $variationId;
        $this->unitPrice       = $unitPrice;
        $this->minQuantity     = $minQuantity;
        $this->maxQuantity     = $maxQuantity;
        $this->discountPercent = $discountPercent;
        $this->markupPercent   = $markupPercent;
        $this->currencyCode    = $currencyCode;
        $this->uomCode         = $uomCode;
        $this->isActive        = $isActive;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}

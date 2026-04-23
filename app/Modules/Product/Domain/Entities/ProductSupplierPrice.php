<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductSupplierPrice
{
    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variantId;
    private int $supplierId;
    private ?int $currencyId;
    private int $uomId;
    private string $minOrderQuantity;
    private string $unitPrice;
    private string $discountPercent;
    private int $leadTimeDays;
    private bool $isPreferred;
    private bool $isActive;
    private ?\DateTimeInterface $effectiveFrom;
    private ?\DateTimeInterface $effectiveTo;
    private ?string $notes;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        int $productId,
        int $supplierId,
        int $uomId,
        string $unitPrice,
        ?int $variantId = null,
        ?int $currencyId = null,
        string $minOrderQuantity = '1',
        string $discountPercent = '0',
        int $leadTimeDays = 0,
        bool $isPreferred = false,
        bool $isActive = true,
        ?\DateTimeInterface $effectiveFrom = null,
        ?\DateTimeInterface $effectiveTo = null,
        ?string $notes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->supplierId = $supplierId;
        $this->currencyId = $currencyId;
        $this->uomId = $uomId;
        $this->minOrderQuantity = $minOrderQuantity;
        $this->unitPrice = $unitPrice;
        $this->discountPercent = $discountPercent;
        $this->leadTimeDays = $leadTimeDays;
        $this->isPreferred = $isPreferred;
        $this->isActive = $isActive;
        $this->effectiveFrom = $effectiveFrom;
        $this->effectiveTo = $effectiveTo;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getSupplierId(): int { return $this->supplierId; }
    public function getCurrencyId(): ?int { return $this->currencyId; }
    public function getUomId(): int { return $this->uomId; }
    public function getMinOrderQuantity(): string { return $this->minOrderQuantity; }
    public function getUnitPrice(): string { return $this->unitPrice; }
    public function getDiscountPercent(): string { return $this->discountPercent; }
    public function getLeadTimeDays(): int { return $this->leadTimeDays; }
    public function isPreferred(): bool { return $this->isPreferred; }
    public function isActive(): bool { return $this->isActive; }
    public function getEffectiveFrom(): ?\DateTimeInterface { return $this->effectiveFrom; }
    public function getEffectiveTo(): ?\DateTimeInterface { return $this->effectiveTo; }
    public function getNotes(): ?string { return $this->notes; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        int $supplierId,
        int $uomId,
        string $unitPrice,
        ?int $variantId,
        ?int $currencyId,
        string $minOrderQuantity,
        string $discountPercent,
        int $leadTimeDays,
        bool $isPreferred,
        bool $isActive,
        ?\DateTimeInterface $effectiveFrom,
        ?\DateTimeInterface $effectiveTo,
        ?string $notes,
        ?array $metadata,
    ): void {
        $this->supplierId = $supplierId;
        $this->variantId = $variantId;
        $this->currencyId = $currencyId;
        $this->uomId = $uomId;
        $this->minOrderQuantity = $minOrderQuantity;
        $this->unitPrice = $unitPrice;
        $this->discountPercent = $discountPercent;
        $this->leadTimeDays = $leadTimeDays;
        $this->isPreferred = $isPreferred;
        $this->isActive = $isActive;
        $this->effectiveFrom = $effectiveFrom;
        $this->effectiveTo = $effectiveTo;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

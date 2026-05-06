<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductVariant
{
    private ?int $id;

    private ?int $tenantId;

    private ?int $orgUnitId;

    private int $productId;

    private ?string $sku;

    private string $name;

    private bool $isDefault;

    private bool $isActive;

    private ?string $purchasePrice;

    private ?string $salesPrice;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $productId,
        string $name,
        ?int $tenantId = null,
        ?int $orgUnitId = null,
        ?string $sku = null,
        bool $isDefault = false,
        bool $isActive = true,
        ?string $purchasePrice = null,
        ?string $salesPrice = null,
        ?array $metadata = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->orgUnitId = $orgUnitId;
        $this->productId = $productId;
        $this->sku = $sku;
        $this->name = $name;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->purchasePrice = $purchasePrice;
        $this->salesPrice = $salesPrice;
        $this->metadata = $metadata;
        $this->rowVersion = $rowVersion;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    public function getSalesPrice(): ?string
    {
        return $this->salesPrice;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }


    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        ?int $orgUnitId,
        string $name,
        ?string $sku,
        bool $isDefault,
        bool $isActive,
        ?string $purchasePrice,
        ?string $salesPrice,
        ?array $metadata,
    ): void {
        $this->orgUnitId = $orgUnitId;
        $this->name = $name;
        $this->sku = $sku;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->purchasePrice = $purchasePrice;
        $this->salesPrice = $salesPrice;
        $this->metadata = $metadata;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable();
    }
}

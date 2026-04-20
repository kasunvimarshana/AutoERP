<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductVariant
{
    private ?int $id;

    private ?int $tenantId;

    private int $productId;

    private ?string $sku;

    private string $name;

    private bool $isDefault;

    private bool $isActive;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $productId,
        ?int $tenantId = null,
        string $name,
        ?string $sku = null,
        bool $isDefault = false,
        bool $isActive = true,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->sku = $sku;
        $this->name = $name;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
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

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        string $name,
        ?string $sku,
        bool $isDefault,
        bool $isActive,
        ?array $metadata,
    ): void {
        $this->name = $name;
        $this->sku = $sku;
        $this->isDefault = $isDefault;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

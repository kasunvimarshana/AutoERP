<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class VariantAttribute
{
    private ?int $id;

    private int $tenantId;

    private int $productId;

    private int $attributeId;

    private bool $isRequired;

    private bool $isVariationAxis;

    private int $displayOrder;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        int $attributeId,
        bool $isRequired = false,
        bool $isVariationAxis = true,
        int $displayOrder = 0,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->attributeId = $attributeId;
        $this->isRequired = $isRequired;
        $this->isVariationAxis = $isVariationAxis;
        $this->displayOrder = $displayOrder;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function isVariationAxis(): bool
    {
        return $this->isVariationAxis;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(bool $isRequired, bool $isVariationAxis, int $displayOrder): void
    {
        $this->isRequired = $isRequired;
        $this->isVariationAxis = $isVariationAxis;
        $this->displayOrder = $displayOrder;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

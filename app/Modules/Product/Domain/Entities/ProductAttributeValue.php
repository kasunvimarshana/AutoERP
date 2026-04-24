<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductAttributeValue
{
    private ?int $id;

    private ?int $tenantId;

    private int $attributeId;

    private string $value;

    private int $sortOrder;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $attributeId,
        string $value,
        int $sortOrder = 0,
        ?int $tenantId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->attributeId = $attributeId;
        $this->value = $value;
        $this->sortOrder = $sortOrder;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(string $value, int $sortOrder): void
    {
        $this->value = $value;
        $this->sortOrder = $sortOrder;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

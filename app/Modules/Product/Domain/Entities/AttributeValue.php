<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class AttributeValue
{
    private ?int $id;
    private int $tenantId;
    private int $attributeId;
    private string $value;
    private int $sortOrder;
    private ?string $label;
    private ?string $colorCode;
    private bool $isActive;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        int $attributeId,
        string $value,
        int $sortOrder = 0,
        ?string $label = null,
        ?string $colorCode = null,
        bool $isActive = true,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->attributeId = $attributeId;
        $this->value = $value;
        $this->sortOrder = $sortOrder;
        $this->label = $label;
        $this->colorCode = $colorCode;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getAttributeId(): int { return $this->attributeId; }
    public function getValue(): string { return $this->value; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function getLabel(): ?string { return $this->label; }
    public function getColorCode(): ?string { return $this->colorCode; }
    public function isActive(): bool { return $this->isActive; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $value,
        int $sortOrder,
        ?string $label,
        ?string $colorCode,
        bool $isActive,
        ?array $metadata,
    ): void {
        $this->value = $value;
        $this->sortOrder = $sortOrder;
        $this->label = $label;
        $this->colorCode = $colorCode;
        $this->isActive = $isActive;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

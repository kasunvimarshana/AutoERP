<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ComboItem
{
    private ?int $id;
    private int $tenantId;
    private int $comboProductId;
    private int $componentProductId;
    private ?int $componentVariantId;
    private string $quantity;
    private int $uomId;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private int $sortOrder;
    private bool $isOptional;
    private ?string $notes;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        int $comboProductId,
        int $componentProductId,
        string $quantity,
        int $uomId,
        ?int $componentVariantId = null,
        ?array $metadata = null,
        int $sortOrder = 0,
        bool $isOptional = false,
        ?string $notes = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->comboProductId = $comboProductId;
        $this->componentProductId = $componentProductId;
        $this->componentVariantId = $componentVariantId;
        $this->quantity = $quantity;
        $this->uomId = $uomId;
        $this->metadata = $metadata;
        $this->sortOrder = $sortOrder;
        $this->isOptional = $isOptional;
        $this->notes = $notes;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getComboProductId(): int { return $this->comboProductId; }
    public function getComponentProductId(): int { return $this->componentProductId; }
    public function getComponentVariantId(): ?int { return $this->componentVariantId; }
    public function getQuantity(): string { return $this->quantity; }
    public function getUomId(): int { return $this->uomId; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isOptional(): bool { return $this->isOptional; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $quantity,
        int $uomId,
        ?int $componentVariantId,
        ?array $metadata,
        int $sortOrder,
        bool $isOptional,
        ?string $notes,
    ): void {
        $this->quantity = $quantity;
        $this->uomId = $uomId;
        $this->componentVariantId = $componentVariantId;
        $this->metadata = $metadata;
        $this->sortOrder = $sortOrder;
        $this->isOptional = $isOptional;
        $this->notes = $notes;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ComboItem
{
    private ?int $id;

    private ?int $tenantId;

    private int $comboProductId;

    private int $componentProductId;

    private ?int $componentVariantId;

    private string $quantity;

    private int $uomId;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        int $comboProductId,
        int $componentProductId,
        string $quantity,
        int $uomId,
        ?int $tenantId = null,
        ?int $componentVariantId = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->comboProductId = $comboProductId;
        $this->componentProductId = $componentProductId;
        $this->componentVariantId = $componentVariantId;
        $this->quantity = $quantity;
        $this->uomId = $uomId;
        $this->metadata = $metadata;
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

    public function getComboProductId(): int
    {
        return $this->comboProductId;
    }

    public function getComponentProductId(): int
    {
        return $this->componentProductId;
    }

    public function getComponentVariantId(): ?int
    {
        return $this->componentVariantId;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUomId(): int
    {
        return $this->uomId;
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
     * @param array<string, mixed>|null $metadata
     */
    public function update(
        int $componentProductId,
        ?int $componentVariantId,
        string $quantity,
        int $uomId,
        ?array $metadata,
    ): void {
        $this->componentProductId = $componentProductId;
        $this->componentVariantId = $componentVariantId;
        $this->quantity = $quantity;
        $this->uomId = $uomId;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

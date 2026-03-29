<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Money;

/**
 * Represents one component item in a combo product.
 * Each combo item references another product by ID and specifies quantity and an optional price override.
 */
class ComboItem
{
    private ?int $id;

    private int $productId;

    private int $tenantId;

    private int $componentProductId;

    private float $quantity;

    private ?Money $priceOverride;

    private int $sortOrder;

    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $productId,
        int $tenantId,
        int $componentProductId,
        float $quantity,
        ?Money $priceOverride = null,
        int $sortOrder = 0,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('ComboItem quantity must be greater than zero.');
        }

        $this->id                 = $id;
        $this->productId          = $productId;
        $this->tenantId           = $tenantId;
        $this->componentProductId = $componentProductId;
        $this->quantity           = $quantity;
        $this->priceOverride      = $priceOverride;
        $this->sortOrder          = $sortOrder;
        $this->metadata           = $metadata;
        $this->createdAt          = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt          = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getComponentProductId(): int
    {
        return $this->componentProductId;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getPriceOverride(): ?Money
    {
        return $this->priceOverride;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

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

    public function updateDetails(
        float $quantity,
        ?Money $priceOverride,
        int $sortOrder,
        ?array $metadata
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('ComboItem quantity must be greater than zero.');
        }

        $this->quantity      = $quantity;
        $this->priceOverride = $priceOverride;
        $this->sortOrder     = $sortOrder;
        $this->metadata      = $metadata;
        $this->updatedAt     = new \DateTimeImmutable;
    }
}

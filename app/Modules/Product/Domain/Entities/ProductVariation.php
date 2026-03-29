<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;

/**
 * Represents one specific variation of a variable product (e.g., Size=M, Color=Red).
 * Each variation has its own SKU and price.
 */
class ProductVariation
{
    private ?int $id;

    private int $productId;

    private int $tenantId;

    private Sku $sku;

    private string $name;

    private Money $price;

    /** @var array<string, string> Attribute code => value pairs, e.g. ['color' => 'Red', 'size' => 'M'] */
    private array $attributeValues;

    private string $status;

    private int $sortOrder;

    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, string>  $attributeValues
     */
    public function __construct(
        int $productId,
        int $tenantId,
        Sku $sku,
        string $name,
        Money $price,
        array $attributeValues = [],
        string $status = 'active',
        int $sortOrder = 0,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id              = $id;
        $this->productId       = $productId;
        $this->tenantId        = $tenantId;
        $this->sku             = $sku;
        $this->name            = $name;
        $this->price           = $price;
        $this->attributeValues = $attributeValues;
        $this->status          = $status;
        $this->sortOrder       = $sortOrder;
        $this->metadata        = $metadata;
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
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

    public function getSku(): Sku
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    /** @return array<string, string> */
    public function getAttributeValues(): array
    {
        return $this->attributeValues;
    }

    public function getAttributeValue(string $code): ?string
    {
        return $this->attributeValues[$code] ?? null;
    }

    public function getStatus(): string
    {
        return $this->status;
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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function activate(): void
    {
        $this->status    = 'active';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->status    = 'inactive';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        string $name,
        Money $price,
        array $attributeValues,
        string $status,
        int $sortOrder,
        ?array $metadata
    ): void {
        $this->name            = $name;
        $this->price           = $price;
        $this->attributeValues = $attributeValues;
        $this->status          = $status;
        $this->sortOrder       = $sortOrder;
        $this->metadata        = $metadata;
        $this->updatedAt       = new \DateTimeImmutable;
    }
}

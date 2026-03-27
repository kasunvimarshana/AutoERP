<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;

class Product
{
    private ?int $id;

    private int $tenantId;

    private Sku $sku;

    private string $name;

    private ?string $description;

    private Money $price;

    private ?string $category;

    private string $status;

    private ?array $attributes;

    private ?array $metadata;

    private Collection $images;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        Sku $sku,
        string $name,
        Money $price,
        ?string $description = null,
        ?string $category = null,
        string $status = 'active',
        ?array $attributes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->sku = $sku;
        $this->name = $name;
        $this->price = $price;
        $this->description = $description;
        $this->category = $category;
        $this->status = $status;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->images = new Collection;
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

    public function getSku(): Sku
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getPrimaryImage(): ?ProductImage
    {
        return $this->images->first(fn (ProductImage $img) => $img->isPrimary()) ?? $this->images->first();
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setImages(Collection $images): void
    {
        $this->images = $images;
    }

    public function addImage(ProductImage $image): void
    {
        $this->images->push($image);
    }

    public function updateDetails(
        string $name,
        Money $price,
        ?string $description,
        ?string $category,
        ?array $attributes,
        ?array $metadata
    ): void {
        $this->name = $name;
        $this->price = $price;
        $this->description = $description;
        $this->category = $category;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

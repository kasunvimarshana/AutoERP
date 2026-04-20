<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductBrand
{
    private ?int $id;

    private int $tenantId;

    private ?int $parentId;

    private string $name;

    private ?string $imagePath;

    private string $slug;

    private ?string $code;

    private ?string $path;

    private int $depth;

    private bool $isActive;

    private ?string $website;

    private ?string $description;

    /** @var array<string, mixed>|null */
    private ?array $attributes;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $attributes
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        string $name,
        string $slug,
        ?string $imagePath = null,
        ?int $parentId = null,
        ?string $code = null,
        ?string $path = null,
        int $depth = 0,
        bool $isActive = true,
        ?string $website = null,
        ?string $description = null,
        ?array $attributes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->parentId = $parentId;
        $this->name = $name;
        $this->imagePath = $imagePath;
        $this->slug = $slug;
        $this->code = $code;
        $this->path = $path;
        $this->depth = $depth;
        $this->isActive = $isActive;
        $this->website = $website;
        $this->description = $description;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
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

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAttributes(): ?array
    {
        return $this->attributes;
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
     * @param  array<string, mixed>|null  $attributes
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        string $name,
        string $slug,
        ?string $imagePath,
        ?int $parentId,
        ?string $code,
        ?string $path,
        int $depth,
        bool $isActive,
        ?string $website,
        ?string $description,
        ?array $attributes,
        ?array $metadata,
    ): void {
        $this->name = $name;
        $this->imagePath = $imagePath;
        $this->slug = $slug;
        $this->parentId = $parentId;
        $this->code = $code;
        $this->path = $path;
        $this->depth = $depth;
        $this->isActive = $isActive;
        $this->website = $website;
        $this->description = $description;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

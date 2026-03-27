<?php

declare(strict_types=1);

namespace Modules\Category\Domain\Entities;

use Illuminate\Support\Collection;

class Category
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private string $slug;

    private ?string $description;

    private ?int $parentId;

    private int $depth;

    private string $path;

    private string $status;

    private ?array $attributes;

    private ?array $metadata;

    private ?CategoryImage $image;

    private Collection $children;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $slug,
        ?string $description = null,
        ?int $parentId = null,
        int $depth = 0,
        string $path = '',
        string $status = 'active',
        ?array $attributes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->parentId = $parentId;
        $this->depth = $depth;
        $this->path = $path;
        $this->status = $status;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->image = null;
        $this->children = new Collection;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getPath(): string
    {
        return $this->path;
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

    public function getImage(): ?CategoryImage
    {
        return $this->image;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setImage(?CategoryImage $image): void
    {
        $this->image = $image;
    }

    public function setChildren(Collection $children): void
    {
        $this->children = $children;
    }

    public function addChild(Category $child): void
    {
        $this->children->push($child);
    }

    public function updateDetails(
        string $name,
        string $slug,
        ?string $description,
        ?int $parentId,
        string $path,
        int $depth,
        ?array $attributes,
        ?array $metadata
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->parentId = $parentId;
        $this->path = $path;
        $this->depth = $depth;
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

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function hasChildren(): bool
    {
        return $this->children->isNotEmpty();
    }
}

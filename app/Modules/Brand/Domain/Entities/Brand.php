<?php

declare(strict_types=1);

namespace Modules\Brand\Domain\Entities;

class Brand
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private string $slug;

    private ?string $description;

    private ?string $website;

    private string $status;

    private ?array $attributes;

    private ?array $metadata;

    private ?BrandLogo $logo;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $slug,
        ?string $description = null,
        ?string $website = null,
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
        $this->website = $website;
        $this->status = $status;
        $this->attributes = $attributes;
        $this->metadata = $metadata;
        $this->logo = null;
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

    public function getWebsite(): ?string
    {
        return $this->website;
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

    public function getLogo(): ?BrandLogo
    {
        return $this->logo;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setLogo(?BrandLogo $logo): void
    {
        $this->logo = $logo;
    }

    public function updateDetails(
        string $name,
        string $slug,
        ?string $description,
        ?string $website,
        ?array $attributes,
        ?array $metadata
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->website = $website;
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

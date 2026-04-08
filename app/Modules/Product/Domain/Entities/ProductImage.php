<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductImage
{
    private ?int $id;

    private int $tenantId;

    private int $productId;

    private string $uuid;

    private string $name;

    private string $filePath;

    private string $mimeType;

    private int $size;

    private int $sortOrder;

    private bool $isPrimary;

    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        string $uuid,
        string $name,
        string $filePath,
        string $mimeType,
        int $size,
        int $sortOrder = 0,
        bool $isPrimary = false,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->uuid = $uuid;
        $this->name = $name;
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->sortOrder = $sortOrder;
        $this->isPrimary = $isPrimary;
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

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
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
}

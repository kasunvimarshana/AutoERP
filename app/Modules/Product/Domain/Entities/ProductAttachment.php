<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductAttachment
{
    /** @var array<string> */
    private const SUPPORTED_TYPES = ['image', 'document', 'video', 'other'];

    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variantId;
    private string $fileName;
    private string $filePath;
    private string $fileType;
    private int $fileSize;
    private string $type;
    private bool $isPrimary;
    private int $sortOrder;
    private ?string $title;
    private ?string $description;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        int $productId,
        string $fileName,
        string $filePath,
        string $fileType,
        int $fileSize,
        ?int $variantId = null,
        string $type = 'image',
        bool $isPrimary = false,
        int $sortOrder = 0,
        ?string $title = null,
        ?string $description = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported attachment type.');
        }
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->fileType = $fileType;
        $this->fileSize = $fileSize;
        $this->type = $type;
        $this->isPrimary = $isPrimary;
        $this->sortOrder = $sortOrder;
        $this->title = $title;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getFileName(): string { return $this->fileName; }
    public function getFilePath(): string { return $this->filePath; }
    public function getFileType(): string { return $this->fileType; }
    public function getFileSize(): int { return $this->fileSize; }
    public function getType(): string { return $this->type; }
    public function isPrimary(): bool { return $this->isPrimary; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function getTitle(): ?string { return $this->title; }
    public function getDescription(): ?string { return $this->description; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $fileName,
        string $filePath,
        string $fileType,
        int $fileSize,
        string $type,
        bool $isPrimary,
        int $sortOrder,
        ?string $title,
        ?string $description,
        ?array $metadata,
    ): void {
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported attachment type.');
        }
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->fileType = $fileType;
        $this->fileSize = $fileSize;
        $this->type = $type;
        $this->isPrimary = $isPrimary;
        $this->sortOrder = $sortOrder;
        $this->title = $title;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

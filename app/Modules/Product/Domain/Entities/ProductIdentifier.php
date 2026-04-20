<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductIdentifier
{
    private ?int $id;

    private int $tenantId;

    private int $productId;

    private ?int $variantId;

    private ?int $batchId;

    private ?int $serialId;

    private string $technology;

    private ?string $format;

    private string $value;

    private ?string $gs1CompanyPrefix;

    /** @var array<string, mixed>|null */
    private ?array $gs1ApplicationIdentifiers;

    private bool $isPrimary;

    private bool $isActive;

    /** @var array<string, mixed>|null */
    private ?array $formatConfig;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param  array<string, mixed>|null  $gs1ApplicationIdentifiers
     * @param  array<string, mixed>|null  $formatConfig
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $productId,
        string $technology,
        string $value,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        ?string $format = null,
        ?string $gs1CompanyPrefix = null,
        ?array $gs1ApplicationIdentifiers = null,
        bool $isPrimary = false,
        bool $isActive = true,
        ?array $formatConfig = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->technology = $technology;
        $this->format = $format;
        $this->value = $value;
        $this->gs1CompanyPrefix = $gs1CompanyPrefix;
        $this->gs1ApplicationIdentifiers = $gs1ApplicationIdentifiers;
        $this->isPrimary = $isPrimary;
        $this->isActive = $isActive;
        $this->formatConfig = $formatConfig;
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

    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    public function getBatchId(): ?int
    {
        return $this->batchId;
    }

    public function getSerialId(): ?int
    {
        return $this->serialId;
    }

    public function getTechnology(): string
    {
        return $this->technology;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getGs1CompanyPrefix(): ?string
    {
        return $this->gs1CompanyPrefix;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getGs1ApplicationIdentifiers(): ?array
    {
        return $this->gs1ApplicationIdentifiers;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFormatConfig(): ?array
    {
        return $this->formatConfig;
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
     * @param  array<string, mixed>|null  $gs1ApplicationIdentifiers
     * @param  array<string, mixed>|null  $formatConfig
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        int $productId,
        string $technology,
        string $value,
        ?int $variantId,
        ?int $batchId,
        ?int $serialId,
        ?string $format,
        ?string $gs1CompanyPrefix,
        ?array $gs1ApplicationIdentifiers,
        bool $isPrimary,
        bool $isActive,
        ?array $formatConfig,
        ?array $metadata,
    ): void {
        $this->productId = $productId;
        $this->technology = $technology;
        $this->value = $value;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->format = $format;
        $this->gs1CompanyPrefix = $gs1CompanyPrefix;
        $this->gs1ApplicationIdentifiers = $gs1ApplicationIdentifiers;
        $this->isPrimary = $isPrimary;
        $this->isActive = $isActive;
        $this->formatConfig = $formatConfig;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

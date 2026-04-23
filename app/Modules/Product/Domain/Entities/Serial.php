<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Serial
{
    /** @var array<string> */
    private const SUPPORTED_STATUSES = ['available', 'reserved', 'sold', 'returned', 'defective', 'scrapped'];

    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variantId;
    private ?int $batchId;
    private string $serialNumber;
    private string $status;
    private ?\DateTimeInterface $soldAt;
    private ?string $notes;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        int $productId,
        string $serialNumber,
        ?int $variantId = null,
        ?int $batchId = null,
        string $status = 'available',
        ?\DateTimeInterface $soldAt = null,
        ?string $notes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        if (! in_array($status, self::SUPPORTED_STATUSES, true)) {
            throw new \InvalidArgumentException('Unsupported serial status.');
        }
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialNumber = $serialNumber;
        $this->status = $status;
        $this->soldAt = $soldAt;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getSerialNumber(): string { return $this->serialNumber; }
    public function getStatus(): string { return $this->status; }
    public function getSoldAt(): ?\DateTimeInterface { return $this->soldAt; }
    public function getNotes(): ?string { return $this->notes; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $status,
        ?\DateTimeInterface $soldAt,
        ?string $notes,
        ?array $metadata,
    ): void {
        if (! in_array($status, self::SUPPORTED_STATUSES, true)) {
            throw new \InvalidArgumentException('Unsupported serial status.');
        }
        $this->status = $status;
        $this->soldAt = $soldAt;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

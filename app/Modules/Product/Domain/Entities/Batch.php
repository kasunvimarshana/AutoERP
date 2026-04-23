<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Batch
{
    /** @var array<string> */
    private const SUPPORTED_STATUSES = ['active', 'quarantine', 'expired', 'consumed'];

    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variantId;
    private string $batchNumber;
    private ?string $lotNumber;
    private ?\DateTimeInterface $manufacturedDate;
    private ?\DateTimeInterface $expiryDate;
    private string $quantity;
    private string $status;
    private ?string $notes;
    /** @var array<string,mixed>|null */
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        int $tenantId,
        int $productId,
        string $batchNumber,
        ?int $variantId = null,
        ?string $lotNumber = null,
        ?\DateTimeInterface $manufacturedDate = null,
        ?\DateTimeInterface $expiryDate = null,
        string $quantity = '0',
        string $status = 'active',
        ?string $notes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        if (! in_array($status, self::SUPPORTED_STATUSES, true)) {
            throw new \InvalidArgumentException('Unsupported batch status.');
        }
        $this->tenantId = $tenantId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->batchNumber = $batchNumber;
        $this->lotNumber = $lotNumber;
        $this->manufacturedDate = $manufacturedDate;
        $this->expiryDate = $expiryDate;
        $this->quantity = $quantity;
        $this->status = $status;
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
    public function getBatchNumber(): string { return $this->batchNumber; }
    public function getLotNumber(): ?string { return $this->lotNumber; }
    public function getManufacturedDate(): ?\DateTimeInterface { return $this->manufacturedDate; }
    public function getExpiryDate(): ?\DateTimeInterface { return $this->expiryDate; }
    public function getQuantity(): string { return $this->quantity; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    /** @return array<string,mixed>|null */
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @param array<string,mixed>|null $metadata */
    public function update(
        string $batchNumber,
        ?string $lotNumber,
        ?\DateTimeInterface $manufacturedDate,
        ?\DateTimeInterface $expiryDate,
        string $quantity,
        string $status,
        ?string $notes,
        ?array $metadata,
    ): void {
        if (! in_array($status, self::SUPPORTED_STATUSES, true)) {
            throw new \InvalidArgumentException('Unsupported batch status.');
        }
        $this->batchNumber = $batchNumber;
        $this->lotNumber = $lotNumber;
        $this->manufacturedDate = $manufacturedDate;
        $this->expiryDate = $expiryDate;
        $this->quantity = $quantity;
        $this->status = $status;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class Batch
{
    private ?int $id;

    public function __construct(
        private int $tenantId,
        private int $productId,
        private ?int $variantId,
        private string $batchNumber,
        private ?string $lotNumber,
        private ?string $manufactureDate,
        private ?string $expiryDate,
        private ?string $receivedDate,
        private ?int $supplierId,
        private string $status,
        private ?string $notes,
        private ?array $metadata,
        private ?string $salesPrice,
        ?int $id = null,
    ) {
        $this->id = $id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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

    public function getBatchNumber(): string
    {
        return $this->batchNumber;
    }

    public function getLotNumber(): ?string
    {
        return $this->lotNumber;
    }

    public function getManufactureDate(): ?string
    {
        return $this->manufactureDate;
    }

    public function getExpiryDate(): ?string
    {
        return $this->expiryDate;
    }

    public function getReceivedDate(): ?string
    {
        return $this->receivedDate;
    }

    public function getSupplierId(): ?int
    {
        return $this->supplierId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getSalesPrice(): ?string
    {
        return $this->salesPrice;
    }

    public function update(
        ?int $variantId,
        string $batchNumber,
        ?string $lotNumber,
        ?string $manufactureDate,
        ?string $expiryDate,
        ?string $receivedDate,
        ?int $supplierId,
        string $status,
        ?string $notes,
        ?array $metadata,
        ?string $salesPrice,
    ): void {
        $this->variantId      = $variantId;
        $this->batchNumber    = $batchNumber;
        $this->lotNumber      = $lotNumber;
        $this->manufactureDate = $manufactureDate;
        $this->expiryDate     = $expiryDate;
        $this->receivedDate   = $receivedDate;
        $this->supplierId     = $supplierId;
        $this->status         = $status;
        $this->notes          = $notes;
        $this->metadata       = $metadata;
        $this->salesPrice     = $salesPrice;
    }
}

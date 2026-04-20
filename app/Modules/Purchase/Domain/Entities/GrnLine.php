<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class GrnLine
{
    private ?int $id;

    private int $tenantId;

    private int $grnHeaderId;

    private ?int $purchaseOrderLineId;

    private int $productId;

    private ?int $variantId;

    private ?int $batchId;

    private ?int $serialId;

    private int $locationId;

    private int $uomId;

    private string $expectedQty;

    private string $receivedQty;

    private string $rejectedQty;

    private string $unitCost;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $grnHeaderId,
        int $productId,
        int $locationId,
        int $uomId,
        string $receivedQty,
        string $unitCost,
        string $expectedQty = '0',
        string $rejectedQty = '0',
        ?int $purchaseOrderLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->grnHeaderId = $grnHeaderId;
        $this->productId = $productId;
        $this->locationId = $locationId;
        $this->uomId = $uomId;
        $this->receivedQty = $receivedQty;
        $this->unitCost = $unitCost;
        $this->expectedQty = $expectedQty;
        $this->rejectedQty = $rejectedQty;
        $this->purchaseOrderLineId = $purchaseOrderLineId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->id = $id;
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

    public function getGrnHeaderId(): int
    {
        return $this->grnHeaderId;
    }

    public function getPurchaseOrderLineId(): ?int
    {
        return $this->purchaseOrderLineId;
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

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getExpectedQty(): string
    {
        return $this->expectedQty;
    }

    public function getReceivedQty(): string
    {
        return $this->receivedQty;
    }

    public function getRejectedQty(): string
    {
        return $this->rejectedQty;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        int $productId,
        int $locationId,
        int $uomId,
        string $receivedQty,
        string $unitCost,
        string $expectedQty = '0',
        string $rejectedQty = '0',
        ?int $purchaseOrderLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
    ): void {
        $this->productId = $productId;
        $this->locationId = $locationId;
        $this->uomId = $uomId;
        $this->receivedQty = $receivedQty;
        $this->unitCost = $unitCost;
        $this->expectedQty = $expectedQty;
        $this->rejectedQty = $rejectedQty;
        $this->purchaseOrderLineId = $purchaseOrderLineId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

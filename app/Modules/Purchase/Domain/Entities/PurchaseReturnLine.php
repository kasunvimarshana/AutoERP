<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class PurchaseReturnLine
{
    private ?int $id;

    private int $tenantId;

    private int $purchaseReturnId;

    private ?int $originalGrnLineId;

    private int $productId;

    private ?int $variantId;

    private ?int $batchId;

    private ?int $serialId;

    private int $fromLocationId;

    private int $uomId;

    private string $returnQty;

    private string $unitCost;

    private string $condition;

    private string $disposition;

    private string $restockingFee;

    private ?string $qualityCheckNotes;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $purchaseReturnId,
        int $productId,
        int $fromLocationId,
        int $uomId,
        string $returnQty,
        string $unitCost,
        string $condition,
        string $disposition,
        string $restockingFee = '0',
        ?int $originalGrnLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        ?string $qualityCheckNotes = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->purchaseReturnId = $purchaseReturnId;
        $this->productId = $productId;
        $this->fromLocationId = $fromLocationId;
        $this->uomId = $uomId;
        $this->returnQty = $returnQty;
        $this->unitCost = $unitCost;
        $this->condition = $condition;
        $this->disposition = $disposition;
        $this->restockingFee = $restockingFee;
        $this->originalGrnLineId = $originalGrnLineId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->qualityCheckNotes = $qualityCheckNotes;
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

    public function getPurchaseReturnId(): int
    {
        return $this->purchaseReturnId;
    }

    public function getOriginalGrnLineId(): ?int
    {
        return $this->originalGrnLineId;
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

    public function getFromLocationId(): int
    {
        return $this->fromLocationId;
    }

    public function getUomId(): int
    {
        return $this->uomId;
    }

    public function getReturnQty(): string
    {
        return $this->returnQty;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function getDisposition(): string
    {
        return $this->disposition;
    }

    public function getRestockingFee(): string
    {
        return $this->restockingFee;
    }

    public function getQualityCheckNotes(): ?string
    {
        return $this->qualityCheckNotes;
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
        int $fromLocationId,
        int $uomId,
        string $returnQty,
        string $unitCost,
        string $condition,
        string $disposition,
        string $restockingFee = '0',
        ?int $originalGrnLineId = null,
        ?int $variantId = null,
        ?int $batchId = null,
        ?int $serialId = null,
        ?string $qualityCheckNotes = null,
    ): void {
        $this->productId = $productId;
        $this->fromLocationId = $fromLocationId;
        $this->uomId = $uomId;
        $this->returnQty = $returnQty;
        $this->unitCost = $unitCost;
        $this->condition = $condition;
        $this->disposition = $disposition;
        $this->restockingFee = $restockingFee;
        $this->originalGrnLineId = $originalGrnLineId;
        $this->variantId = $variantId;
        $this->batchId = $batchId;
        $this->serialId = $serialId;
        $this->qualityCheckNotes = $qualityCheckNotes;
        $this->updatedAt = new \DateTimeImmutable;
    }
}

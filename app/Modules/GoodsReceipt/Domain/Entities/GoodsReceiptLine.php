<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class GoodsReceiptLine
{
    private ?int $id;
    private int $tenantId;
    private int $goodsReceiptId;
    private int $lineNumber;
    private ?int $purchaseOrderLineId;
    private int $productId;
    private ?int $variationId;
    private ?int $batchId;
    private ?string $serialNumber;
    private ?int $uomId;
    private float $quantityExpected;
    private float $quantityReceived;
    private float $quantityAccepted;
    private float $quantityRejected;
    private float $unitCost;
    private string $condition;
    private ?string $notes;
    private Metadata $metadata;
    private string $status;
    private ?int $putawayLocationId;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $goodsReceiptId,
        int $lineNumber,
        int $productId,
        float $quantityReceived,
        ?int $purchaseOrderLineId = null,
        ?int $variationId = null,
        ?int $batchId = null,
        ?string $serialNumber = null,
        ?int $uomId = null,
        float $quantityExpected = 0.0,
        float $quantityAccepted = 0.0,
        float $quantityRejected = 0.0,
        float $unitCost = 0.0,
        string $condition = 'good',
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = 'pending',
        ?int $putawayLocationId = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                  = $id;
        $this->tenantId            = $tenantId;
        $this->goodsReceiptId      = $goodsReceiptId;
        $this->lineNumber          = $lineNumber;
        $this->purchaseOrderLineId = $purchaseOrderLineId;
        $this->productId           = $productId;
        $this->variationId         = $variationId;
        $this->batchId             = $batchId;
        $this->serialNumber        = $serialNumber;
        $this->uomId               = $uomId;
        $this->quantityExpected    = $quantityExpected;
        $this->quantityReceived    = $quantityReceived;
        $this->quantityAccepted    = $quantityAccepted;
        $this->quantityRejected    = $quantityRejected;
        $this->unitCost            = $unitCost;
        $this->condition           = $condition;
        $this->notes               = $notes;
        $this->metadata            = $metadata ?? new Metadata([]);
        $this->status              = $status;
        $this->putawayLocationId   = $putawayLocationId;
        $this->createdAt           = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt           = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getGoodsReceiptId(): int { return $this->goodsReceiptId; }
    public function getLineNumber(): int { return $this->lineNumber; }
    public function getPurchaseOrderLineId(): ?int { return $this->purchaseOrderLineId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getQuantityExpected(): float { return $this->quantityExpected; }
    public function getQuantityReceived(): float { return $this->quantityReceived; }
    public function getQuantityAccepted(): float { return $this->quantityAccepted; }
    public function getQuantityRejected(): float { return $this->quantityRejected; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getCondition(): string { return $this->condition; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getStatus(): string { return $this->status; }
    public function getPutawayLocationId(): ?int { return $this->putawayLocationId; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function accept(float $qty): void
    {
        $this->quantityAccepted = $qty;
        $this->status           = 'accepted';
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function reject(float $qty): void
    {
        $this->quantityRejected = $qty;
        $this->status           = 'rejected';
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function partialAccept(float $accepted, float $rejected): void
    {
        $this->quantityAccepted = $accepted;
        $this->quantityRejected = $rejected;
        $this->status           = 'partially_accepted';
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function setPutawayLocation(int $locationId): void
    {
        $this->putawayLocationId = $locationId;
        $this->updatedAt         = new \DateTimeImmutable;
    }

    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
}

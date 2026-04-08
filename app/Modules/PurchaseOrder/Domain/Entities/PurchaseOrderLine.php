<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\Entities;

use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderLineStatus;

class PurchaseOrderLine
{
    private ?int $id;
    private int $tenantId;
    private int $purchaseOrderId;
    private int $lineNumber;
    private int $productId;
    private ?int $variationId;
    private ?string $description;
    private ?int $uomId;
    private float $quantityOrdered;
    private float $quantityReceived;
    private float $unitPrice;
    private float $discountPercent;
    private float $taxPercent;
    private float $lineTotal;
    private ?string $expectedDate;
    private ?string $notes;
    private ?array $metadata;
    private string $status;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $purchaseOrderId,
        int $lineNumber,
        int $productId,
        float $quantityOrdered,
        float $unitPrice,
        ?int $variationId = null,
        ?string $description = null,
        ?int $uomId = null,
        float $quantityReceived = 0.0,
        float $discountPercent = 0.0,
        float $taxPercent = 0.0,
        float $lineTotal = 0.0,
        ?string $expectedDate = null,
        ?string $notes = null,
        ?array $metadata = null,
        string $status = PurchaseOrderLineStatus::OPEN,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id               = $id;
        $this->tenantId         = $tenantId;
        $this->purchaseOrderId  = $purchaseOrderId;
        $this->lineNumber       = $lineNumber;
        $this->productId        = $productId;
        $this->variationId      = $variationId;
        $this->description      = $description;
        $this->uomId            = $uomId;
        $this->quantityOrdered  = $quantityOrdered;
        $this->quantityReceived = $quantityReceived;
        $this->unitPrice        = $unitPrice;
        $this->discountPercent  = $discountPercent;
        $this->taxPercent       = $taxPercent;
        $this->lineTotal        = $lineTotal;
        $this->expectedDate     = $expectedDate;
        $this->notes            = $notes;
        $this->metadata         = $metadata;
        $this->status           = $status;
        $this->createdAt        = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt        = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getPurchaseOrderId(): int { return $this->purchaseOrderId; }
    public function getLineNumber(): int { return $this->lineNumber; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getDescription(): ?string { return $this->description; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getQuantityOrdered(): float { return $this->quantityOrdered; }
    public function getQuantityReceived(): float { return $this->quantityReceived; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getDiscountPercent(): float { return $this->discountPercent; }
    public function getTaxPercent(): float { return $this->taxPercent; }
    public function getLineTotal(): float { return $this->lineTotal; }
    public function getExpectedDate(): ?string { return $this->expectedDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function markPartiallyReceived(float $qtyReceived): void
    {
        $this->quantityReceived = $qtyReceived;
        $this->status           = PurchaseOrderLineStatus::PARTIALLY_RECEIVED;
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function markFullyReceived(float $qtyReceived): void
    {
        $this->quantityReceived = $qtyReceived;
        $this->status           = PurchaseOrderLineStatus::FULLY_RECEIVED;
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = PurchaseOrderLineStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function receiveQuantity(float $qty): void
    {
        $this->quantityReceived += $qty;
        $this->status = $this->quantityReceived >= $this->quantityOrdered
            ? PurchaseOrderLineStatus::FULLY_RECEIVED
            : PurchaseOrderLineStatus::PARTIALLY_RECEIVED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isFullyReceived(): bool { return $this->status === PurchaseOrderLineStatus::FULLY_RECEIVED; }
    public function isOpen(): bool { return $this->status === PurchaseOrderLineStatus::OPEN; }

    public function updateDetails(
        float $quantityOrdered,
        float $unitPrice,
        float $discountPercent,
        float $taxPercent,
        float $lineTotal,
        ?string $expectedDate,
        ?string $notes,
        ?array $metadata,
    ): void {
        $this->quantityOrdered = $quantityOrdered;
        $this->unitPrice       = $unitPrice;
        $this->discountPercent = $discountPercent;
        $this->taxPercent      = $taxPercent;
        $this->lineTotal       = $lineTotal;
        if ($expectedDate !== null) { $this->expectedDate = $expectedDate; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadata !== null) { $this->metadata = $metadata; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}

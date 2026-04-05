<?php
declare(strict_types=1);
namespace Modules\Returns\Domain\Entities;

class ReturnLine
{
    public const CONDITION_GOOD       = 'good';
    public const CONDITION_DAMAGED    = 'damaged';
    public const CONDITION_UNSELLABLE = 'unsellable';

    public const QUALITY_PENDING  = 'pending';
    public const QUALITY_APPROVED = 'approved';
    public const QUALITY_REJECTED = 'rejected';

    public function __construct(
        private ?int $id,
        private int $returnRequestId,
        private int $productId,
        private float $quantityReturned,
        private float $unitPrice,
        private ?string $batchNumber,
        private ?string $lotNumber,
        private ?string $serialNumber,
        private ?string $reason,
        private string $condition,
        private string $qualityStatus,
        private ?int $restockedToWarehouseId,
        private ?float $restockedQuantity,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getReturnRequestId(): int { return $this->returnRequestId; }
    public function getProductId(): int { return $this->productId; }
    public function getQuantityReturned(): float { return $this->quantityReturned; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getBatchNumber(): ?string { return $this->batchNumber; }
    public function getLotNumber(): ?string { return $this->lotNumber; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getReason(): ?string { return $this->reason; }
    public function getCondition(): string { return $this->condition; }
    public function getQualityStatus(): string { return $this->qualityStatus; }
    public function getRestockedToWarehouseId(): ?int { return $this->restockedToWarehouseId; }
    public function getRestockedQuantity(): ?float { return $this->restockedQuantity; }

    public function isGoodCondition(): bool { return $this->condition === self::CONDITION_GOOD; }
    public function isDamaged(): bool { return $this->condition === self::CONDITION_DAMAGED; }
    public function isUnsellable(): bool { return $this->condition === self::CONDITION_UNSELLABLE; }
    public function isEligibleForRestock(): bool { return $this->condition !== self::CONDITION_UNSELLABLE; }

    public function approveQuality(): void
    {
        if ($this->qualityStatus !== self::QUALITY_PENDING) {
            throw new \DomainException("Quality check can only be performed on pending lines.");
        }
        $this->qualityStatus = self::QUALITY_APPROVED;
    }

    public function rejectQuality(): void
    {
        if ($this->qualityStatus !== self::QUALITY_PENDING) {
            throw new \DomainException("Quality check can only be performed on pending lines.");
        }
        $this->qualityStatus = self::QUALITY_REJECTED;
    }

    public function recordRestock(int $warehouseId, float $quantity): void
    {
        if ($this->qualityStatus !== self::QUALITY_APPROVED) {
            throw new \DomainException("Cannot restock a line that has not passed quality check.");
        }
        $this->restockedToWarehouseId = $warehouseId;
        $this->restockedQuantity      = $quantity;
    }
}

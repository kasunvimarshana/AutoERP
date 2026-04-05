<?php
declare(strict_types=1);
namespace Modules\StockMovement\Domain\Entities;
class StockMovement {
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_ISSUE = 'issue';
    public const TYPE_TRANSFER = 'transfer';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public function __construct(
        private ?int $id, private int $tenantId, private int $productId,
        private int $warehouseId, private ?int $fromLocationId, private ?int $toLocationId,
        private string $movementType, private float $quantity, private float $unitCost,
        private ?string $reference, private ?string $notes, private ?int $createdBy,
        private ?\DateTimeInterface $movedAt, private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getFromLocationId(): ?int { return $this->fromLocationId; }
    public function getToLocationId(): ?int { return $this->toLocationId; }
    public function getMovementType(): string { return $this->movementType; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getReference(): ?string { return $this->reference; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getMovedAt(): ?\DateTimeInterface { return $this->movedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}

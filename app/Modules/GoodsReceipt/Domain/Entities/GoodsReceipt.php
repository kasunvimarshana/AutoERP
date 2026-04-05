<?php
declare(strict_types=1);
namespace Modules\GoodsReceipt\Domain\Entities;
class GoodsReceipt {
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_INSPECTION = 'under_inspection';
    public const STATUS_INSPECTED = 'inspected';
    public const STATUS_PUT_AWAY = 'put_away';
    public const STATUS_CANCELLED = 'cancelled';
    public function __construct(
        private ?int $id, private int $tenantId, private ?int $purchaseOrderId,
        private int $warehouseId, private string $grNumber, private string $status,
        private ?string $notes, private ?int $receivedBy, private ?int $inspectedBy,
        private ?\DateTimeInterface $inspectedAt, private ?int $putAwayBy,
        private ?\DateTimeInterface $putAwayAt, private array $lines,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getPurchaseOrderId(): ?int { return $this->purchaseOrderId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getGrNumber(): string { return $this->grNumber; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    public function getReceivedBy(): ?int { return $this->receivedBy; }
    public function getInspectedBy(): ?int { return $this->inspectedBy; }
    public function getInspectedAt(): ?\DateTimeInterface { return $this->inspectedAt; }
    public function getPutAwayBy(): ?int { return $this->putAwayBy; }
    public function getPutAwayAt(): ?\DateTimeInterface { return $this->putAwayAt; }
    public function getLines(): array { return $this->lines; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function inspect(int $inspectedBy): void {
        if ($this->status === self::STATUS_CANCELLED) throw new \DomainException("Cannot inspect a cancelled GR.");
        $this->status = self::STATUS_INSPECTED;
        $this->inspectedBy = $inspectedBy;
        $this->inspectedAt = new \DateTimeImmutable();
    }
    public function putAway(int $putAwayBy): void {
        if ($this->status !== self::STATUS_INSPECTED) throw new \DomainException("GR must be inspected before put-away.");
        $this->status = self::STATUS_PUT_AWAY;
        $this->putAwayBy = $putAwayBy;
        $this->putAwayAt = new \DateTimeImmutable();
    }
}

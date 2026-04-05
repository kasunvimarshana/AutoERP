<?php
declare(strict_types=1);
namespace Modules\PurchaseOrder\Domain\Entities;
class PurchaseOrder {
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PARTIAL = 'partially_received';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        private ?int $id, private int $tenantId, private int $supplierId,
        private ?int $warehouseId, private string $poNumber, private string $status,
        private float $totalAmount, private string $currency,
        private ?string $expectedDate, private ?string $notes,
        private ?int $createdBy, private array $lines,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getSupplierId(): int { return $this->supplierId; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getPoNumber(): string { return $this->poNumber; }
    public function getStatus(): string { return $this->status; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getExpectedDate(): ?string { return $this->expectedDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getLines(): array { return $this->lines; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function confirm(): void {
        if (!$this->isDraft()) throw new \DomainException("Only draft POs can be confirmed.");
        $this->status = self::STATUS_CONFIRMED;
    }
    public function cancel(): void {
        if (in_array($this->status,[self::STATUS_RECEIVED,self::STATUS_CANCELLED],true)) throw new \DomainException("Cannot cancel PO in status: {$this->status}");
        $this->status = self::STATUS_CANCELLED;
    }
    public function markReceived(): void { $this->status = self::STATUS_RECEIVED; }
    public function markPartiallyReceived(): void { $this->status = self::STATUS_PARTIAL; }
}

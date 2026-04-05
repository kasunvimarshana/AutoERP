<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Domain\Entities;
class SalesOrder {
    public function __construct(
        private ?int $id, private int $tenantId, private int $customerId,
        private ?int $warehouseId, private string $soNumber, private string $status,
        private float $subtotal, private float $taxAmount, private float $totalAmount,
        private string $currency, private ?string $notes, private ?int $createdBy,
        private array $lines,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getSoNumber(): string { return $this->soNumber; }
    public function getStatus(): string { return $this->status; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getCurrency(): string { return $this->currency; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function getLines(): array { return $this->lines; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isDraft(): bool { return $this->status === 'draft'; }
    public function confirm(): void { if(!$this->isDraft()) throw new \DomainException("Only draft SOs can be confirmed."); $this->status='confirmed'; }
    public function startPicking(): void { if($this->status!=='confirmed') throw new \DomainException("SO must be confirmed before picking."); $this->status='picking'; }
    public function startPacking(): void { if($this->status!=='picking') throw new \DomainException("SO must be in picking state before packing."); $this->status='packing'; }
    public function ship(): void { if($this->status!=='packing') throw new \DomainException("SO must be packed before shipping."); $this->status='shipped'; }
    public function cancel(): void { if(in_array($this->status,['shipped','cancelled'],true)) throw new \DomainException("Cannot cancel SO in status: {$this->status}"); $this->status='cancelled'; }
}

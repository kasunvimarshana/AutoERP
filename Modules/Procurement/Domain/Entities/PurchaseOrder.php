<?php
declare(strict_types=1);
namespace Modules\Procurement\Domain\Entities;
use Modules\Procurement\Domain\Enums\PurchaseStatus;
class PurchaseOrder {
    public function __construct(
        private readonly int      $id,
        private readonly int      $tenantId,
        private readonly int      $vendorId,
        private string            $poNumber,
        private PurchaseStatus    $status,
        private string            $subtotal,
        private string            $taxAmount,
        private string            $total,
        private ?string           $expectedDeliveryDate,
        private ?string           $notes,
        private readonly ?int     $createdBy,
    ) {}
    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getVendorId(): int { return $this->vendorId; }
    public function getPoNumber(): string { return $this->poNumber; }
    public function getStatus(): PurchaseStatus { return $this->status; }
    public function getSubtotal(): string { return $this->subtotal; }
    public function getTaxAmount(): string { return $this->taxAmount; }
    public function getTotal(): string { return $this->total; }
    public function getExpectedDeliveryDate(): ?string { return $this->expectedDeliveryDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function confirm(): void {
        if ($this->status !== PurchaseStatus::DRAFT && $this->status !== PurchaseStatus::SENT) {
            throw new \DomainException('Purchase order can only be confirmed from Draft or Sent status.');
        }
        $this->status = PurchaseStatus::CONFIRMED;
    }
    public function cancel(): void {
        if ($this->status === PurchaseStatus::RECEIVED || $this->status === PurchaseStatus::BILLED) {
            throw new \DomainException('Cannot cancel a fully received or billed purchase order.');
        }
        $this->status = PurchaseStatus::CANCELLED;
    }
    public function calculateTotal(array $lines): void {
        $subtotal = '0.0000';
        $taxTotal = '0.0000';
        foreach ($lines as $line) {
            $net = bcmul((string)$line['quantity'], (string)$line['unit_cost'], 4);
            $tax = bcdiv(bcmul($net, (string)($line['tax_percent'] ?? '0'), 4), '100', 4);
            $subtotal = bcadd($subtotal, $net, 4);
            $taxTotal = bcadd($taxTotal, $tax, 4);
        }
        $this->subtotal  = $subtotal;
        $this->taxAmount = $taxTotal;
        $this->total     = bcadd($subtotal, $taxTotal, 4);
    }
}

<?php
declare(strict_types=1);
namespace Modules\Procurement\Domain\Entities;
class PurchaseOrderLine {
    public function __construct(
        private readonly int    $id,
        private readonly int    $purchaseOrderId,
        private readonly int    $productId,
        private readonly ?int   $variantId,
        private string          $quantity,
        private string          $unitCost,
        private string          $taxPercent,
        private string          $lineTotal,
        private string          $receivedQuantity,
        private ?string         $notes,
    ) {}
    public function getId(): int { return $this->id; }
    public function getPurchaseOrderId(): int { return $this->purchaseOrderId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getQuantity(): string { return $this->quantity; }
    public function getUnitCost(): string { return $this->unitCost; }
    public function getTaxPercent(): string { return $this->taxPercent; }
    public function getLineTotal(): string { return $this->lineTotal; }
    public function getReceivedQuantity(): string { return $this->receivedQuantity; }
    public function getNotes(): ?string { return $this->notes; }
    public function calculateLineTotal(): string {
        $net = bcmul($this->quantity, $this->unitCost, 4);
        $tax = bcdiv(bcmul($net, $this->taxPercent, 4), '100', 4);
        return bcadd($net, $tax, 4);
    }
    public function getRemainingQuantity(): string {
        return bcsub($this->quantity, $this->receivedQuantity, 4);
    }
    public function isFullyReceived(): bool {
        return bccomp($this->receivedQuantity, $this->quantity, 4) >= 0;
    }
}

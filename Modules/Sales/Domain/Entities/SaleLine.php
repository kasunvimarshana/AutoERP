<?php
declare(strict_types=1);
namespace Modules\Sales\Domain\Entities;
class SaleLine {
    public function __construct(
        private readonly int     $id,
        private readonly int     $saleId,
        private readonly int     $productId,
        private readonly ?int    $variantId,
        private string           $quantity,
        private string           $unitPrice,
        private string           $discountPercent,
        private string           $taxPercent,
        private string           $lineTotal,
        private ?string          $notes,
    ) {}
    public function getId(): int { return $this->id; }
    public function getSaleId(): int { return $this->saleId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getQuantity(): string { return $this->quantity; }
    public function getUnitPrice(): string { return $this->unitPrice; }
    public function getDiscountPercent(): string { return $this->discountPercent; }
    public function getTaxPercent(): string { return $this->taxPercent; }
    public function getLineTotal(): string { return $this->lineTotal; }
    public function getNotes(): ?string { return $this->notes; }
    public function calculateLineTotal(): string {
        $gross    = bcmul($this->quantity, $this->unitPrice, 4);
        $discount = bcdiv(bcmul($gross, $this->discountPercent, 4), '100', 4);
        $net      = bcsub($gross, $discount, 4);
        $tax      = bcdiv(bcmul($net, $this->taxPercent, 4), '100', 4);
        return bcadd($net, $tax, 4);
    }
}

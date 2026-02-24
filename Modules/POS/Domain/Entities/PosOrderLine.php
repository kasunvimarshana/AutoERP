<?php

namespace Modules\POS\Domain\Entities;

class PosOrderLine
{
    public function __construct(
        private string $id,
        private string $posOrderId,
        private ?string $productId,
        private string $productName,
        private string $quantity,
        private string $unitPrice,
        private string $discount,
        private string $taxRate,
        private string $lineTotal,
    ) {}

    public function getId(): string { return $this->id; }
    public function getPosOrderId(): string { return $this->posOrderId; }
    public function getProductId(): ?string { return $this->productId; }
    public function getProductName(): string { return $this->productName; }
    public function getQuantity(): string { return $this->quantity; }
    public function getUnitPrice(): string { return $this->unitPrice; }
    public function getDiscount(): string { return $this->discount; }
    public function getTaxRate(): string { return $this->taxRate; }
    public function getLineTotal(): string { return $this->lineTotal; }
}

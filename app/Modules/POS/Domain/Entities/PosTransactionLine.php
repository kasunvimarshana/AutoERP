<?php
declare(strict_types=1);
namespace Modules\POS\Domain\Entities;

/** A single line item within a POS transaction. */
class PosTransactionLine
{
    public function __construct(
        private ?int $id,
        private int $posTransactionId,
        private int $productId,
        private ?int $variantId,
        private string $productName,
        private string $sku,
        private float $quantity,
        private float $unitPrice,
        private float $discountAmount,
        private float $taxAmount,
        private float $lineTotal,
        private ?\DateTimeInterface $createdAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getPosTransactionId(): int { return $this->posTransactionId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getProductName(): string { return $this->productName; }
    public function getSku(): string { return $this->sku; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getLineTotal(): float { return $this->lineTotal; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}

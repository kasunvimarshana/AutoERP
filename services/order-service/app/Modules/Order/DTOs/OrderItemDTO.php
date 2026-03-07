<?php

namespace App\Modules\Order\DTOs;

class OrderItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productSku,
        public readonly string $productName,
        public readonly int $quantity,
        public readonly float $unitPrice,
    ) {}

    public function getTotalPrice(): float
    {
        return $this->quantity * $this->unitPrice;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            productSku: $data['product_sku'],
            productName: $data['product_name'],
            quantity: (int) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_sku' => $this->productSku,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'total_price' => $this->getTotalPrice(),
        ];
    }
}

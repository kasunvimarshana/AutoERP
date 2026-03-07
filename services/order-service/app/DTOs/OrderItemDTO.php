<?php

namespace App\DTOs;

readonly class OrderItemDTO
{
    public function __construct(
        public string  $productId,
        public string  $productName,
        public string  $productSku,
        public int     $quantity,
        public float   $unitPrice,
        public ?array  $attributes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            productId:   $data['product_id'],
            productName: $data['product_name'],
            productSku:  $data['product_sku'],
            quantity:    (int) $data['quantity'],
            unitPrice:   (float) $data['unit_price'],
            attributes:  $data['attributes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id'   => $this->productId,
            'product_name' => $this->productName,
            'product_sku'  => $this->productSku,
            'quantity'     => $this->quantity,
            'unit_price'   => $this->unitPrice,
            'attributes'   => $this->attributes,
        ];
    }
}

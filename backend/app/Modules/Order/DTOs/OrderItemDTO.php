<?php

namespace App\Modules\Order\DTOs;

class OrderItemDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $order_id = null,
        public ?string $product_variant_id = null,
        public ?float $quantity = 1,
        public ?float $unit_price = 0.00,
        public ?float $tax_rate = 0.00,
        public ?float $discount_amount = 0.00,
        public ?float $line_total = 0.00,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            order_id: $data['order_id'] ?? null,
            product_variant_id: $data['product_variant_id'] ?? null,
            quantity: isset($data['quantity']) ? (float)$data['quantity'] : 1,
            unit_price: isset($data['unit_price']) ? (float)$data['unit_price'] : 0.00,
            tax_rate: isset($data['tax_rate']) ? (float)$data['tax_rate'] : 0.00,
            discount_amount: isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0.00,
            line_total: isset($data['line_total']) ? (float)$data['line_total'] : 0.00,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_rate' => $this->tax_rate,
            'discount_amount' => $this->discount_amount,
            'line_total' => $this->line_total,
            'notes' => $this->notes,
        ], fn($value) => !is_null($value));
    }
}

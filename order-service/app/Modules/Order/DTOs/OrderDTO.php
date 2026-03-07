<?php

namespace App\Modules\Order\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly int $user_id,
        public readonly int $product_id,
        public readonly int $quantity,
        public readonly float $total_price,
        public readonly string $status,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'],
            product_id: $data['product_id'],
            quantity: $data['quantity'],
            total_price: $data['total_price'],
            status: $data['status'] ?? 'PENDING'
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'status' => $this->status,
        ];
    }
}

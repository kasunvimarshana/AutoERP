<?php

namespace App\Modules\Order\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly string $status,
        public readonly float $totalAmount,
        public readonly ?string $orderNumber = null,
        public readonly ?array $shippingAddress = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            status: $data['status'] ?? 'pending',
            totalAmount: (float) ($data['total_amount'] ?? 0),
            orderNumber: $data['order_number'] ?? null,
            shippingAddress: $data['shipping_address'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'order_number' => $this->orderNumber,
            'shipping_address' => $this->shippingAddress,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}

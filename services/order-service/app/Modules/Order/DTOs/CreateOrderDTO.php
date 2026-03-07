<?php

namespace App\Modules\Order\DTOs;

class CreateOrderDTO
{
    public function __construct(
        public readonly string $userId,
        public readonly array $items,
        public readonly ?array $shippingAddress = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            items: $data['items'],
            shippingAddress: $data['shipping_address'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}

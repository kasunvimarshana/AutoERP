<?php

namespace App\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly array $items,
        public readonly ?array $shippingAddress = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            items: $data['items'],
            shippingAddress: $data['shipping_address'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}

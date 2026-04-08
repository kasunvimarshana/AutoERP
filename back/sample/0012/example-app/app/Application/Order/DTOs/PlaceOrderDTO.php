<?php

namespace App\Application\Order\DTOs;

/**
 * PlaceOrderDTO — carries validated input for placing a new order.
 */
final class PlaceOrderDTO
{
    public function __construct(
        public readonly string $customerId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: $data['customer_id'],
        );
    }

    public function toArray(): array
    {
        return ['customer_id' => $this->customerId];
    }
}

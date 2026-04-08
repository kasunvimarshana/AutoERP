<?php

namespace App\Application\Order\Commands;

final class PlaceOrderCommand
{
    public function __construct(
        public readonly string $customerId,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(customerId: $data['customer_id']);
    }
}

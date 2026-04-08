<?php

namespace App\Application\Order\Commands;

final class CancelOrderCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $reason,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            orderId: $data['order_id'],
            reason:  $data['reason'] ?? 'No reason provided.',
        );
    }
}

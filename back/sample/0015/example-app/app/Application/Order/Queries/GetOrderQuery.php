<?php

namespace App\Application\Order\Queries;

final class GetOrderQuery
{
    public function __construct(
        public readonly string $orderId,
    ) {
    }
}

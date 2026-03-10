<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use RuntimeException;

class OrderNotFoundException extends RuntimeException
{
    public function __construct(string $orderId)
    {
        parent::__construct("Order [{$orderId}] not found.", 404);
    }
}

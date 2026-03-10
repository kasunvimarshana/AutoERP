<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productId, int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock for product [{$productId}]. "
            . "Requested: {$requested}, Available: {$available}.",
            422
        );
    }
}

<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InsufficientStockException extends HttpException
{
    public function __construct(
        private readonly int    $available,
        private readonly int    $requested,
        private readonly int    $inventoryItemId,
        string                  $message = '',
        ?\Throwable             $previous = null,
    ) {
        $msg = $message ?: "Insufficient stock: requested {$requested}, available {$available}.";

        parent::__construct(409, $msg, $previous);
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function getInventoryItemId(): int
    {
        return $this->inventoryItemId;
    }
}

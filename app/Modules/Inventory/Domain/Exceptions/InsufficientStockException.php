<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forRequested(float $requested, float $available): self
    {
        return new self("Insufficient stock. Requested: {$requested}, available: {$available}.");
    }
}

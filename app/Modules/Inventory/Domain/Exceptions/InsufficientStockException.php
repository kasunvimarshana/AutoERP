<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

final class InsufficientStockException extends DomainException
{
    public function __construct(mixed $productId, float $requested, float $available)
    {
        parent::__construct(
            "Insufficient stock for product [{$productId}]: requested {$requested}, available {$available}.",
            422
        );
    }
}

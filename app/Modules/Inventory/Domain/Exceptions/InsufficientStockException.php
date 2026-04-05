<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InsufficientStockException extends DomainException
{
    public function __construct(int $productId, float $requested, float $available)
    {
        parent::__construct(
            "Insufficient stock for product [{$productId}]. Requested: {$requested}, Available: {$available}."
        );
    }
}

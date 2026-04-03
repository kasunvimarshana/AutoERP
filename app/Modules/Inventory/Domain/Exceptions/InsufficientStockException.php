<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InsufficientStockException extends DomainException
{
    public function __construct(int $productId, float $available, float $requested)
    {
        parent::__construct(
            "Insufficient stock for product {$productId}: available {$available}, requested {$requested}"
        );
    }
}

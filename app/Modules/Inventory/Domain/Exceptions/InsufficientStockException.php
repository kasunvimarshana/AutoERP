<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class InsufficientStockException extends DomainException
{
    public function __construct(string $productId, float $required, float $available)
    {
        parent::__construct(
            sprintf(
                "Insufficient stock for product '%s': required %.4f, available %.4f.",
                $productId,
                $required,
                $available,
            ),
            422,
        );
    }
}

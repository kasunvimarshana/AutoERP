<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use RuntimeException;

class ProductNotFoundException extends RuntimeException
{
    public function __construct(string $productId)
    {
        parent::__construct("Product [{$productId}] not found.", 404);
    }
}

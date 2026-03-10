<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use RuntimeException;

class ProductAlreadyExistsException extends RuntimeException
{
    public function __construct(string $code, string $tenantId)
    {
        parent::__construct("Product with code [{$code}] already exists for tenant [{$tenantId}].", 409);
    }
}

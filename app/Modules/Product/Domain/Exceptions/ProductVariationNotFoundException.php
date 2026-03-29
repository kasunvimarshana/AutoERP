<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use RuntimeException;

class ProductVariationNotFoundException extends RuntimeException
{
    public function __construct(int|string $id)
    {
        parent::__construct("Product variation with ID [{$id}] not found.");
    }
}

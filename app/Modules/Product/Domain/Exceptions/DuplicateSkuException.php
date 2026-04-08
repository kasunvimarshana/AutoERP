<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\DomainException;

class DuplicateSkuException extends DomainException
{
    public function __construct(string $sku)
    {
        parent::__construct("A product with SKU '{$sku}' already exists.", 422);
    }
}

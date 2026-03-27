<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Exceptions;

use Modules\Core\Domain\Exceptions\NotFoundException;

class ProductImageNotFoundException extends NotFoundException
{
    public function __construct(mixed $id)
    {
        parent::__construct('ProductImage', $id);
    }
}

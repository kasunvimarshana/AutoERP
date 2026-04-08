<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Product\Domain\Entities\Product;

class ProductUpdated
{
    public function __construct(
        public readonly Product $product,
    ) {}
}

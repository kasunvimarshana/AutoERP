<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Events;

use Modules\Product\Domain\Entities\Category;

class CategoryCreated
{
    public function __construct(
        public readonly Category $category,
    ) {}
}

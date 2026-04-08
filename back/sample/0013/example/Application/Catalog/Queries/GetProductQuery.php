<?php

declare(strict_types=1);

namespace App\Application\Catalog\Queries;

final class GetProductQuery
{
    public function __construct(
        public readonly string $productId,
    ) {}
}

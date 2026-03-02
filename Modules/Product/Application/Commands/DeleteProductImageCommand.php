<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class DeleteProductImageCommand
{
    public function __construct(
        public int $imageId,
        public int $productId,
        public int $tenantId,
    ) {}
}

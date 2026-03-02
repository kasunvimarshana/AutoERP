<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class DeleteProductAttributeCommand
{
    public function __construct(
        public int $attributeId,
        public int $productId,
        public int $tenantId,
    ) {}
}

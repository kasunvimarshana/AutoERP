<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class AddCartItemCommand
{
    public function __construct(
        public int $tenantId,
        public string $cartToken,
        public int $productId,
        public string $productName,
        public string $sku,
        public string $quantity,
        public string $unitPrice,
    ) {}
}

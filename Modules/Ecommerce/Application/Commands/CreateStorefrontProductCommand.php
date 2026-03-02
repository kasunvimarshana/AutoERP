<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class CreateStorefrontProductCommand
{
    public function __construct(
        public int $tenantId,
        public int $productId,
        public string $slug,
        public string $name,
        public ?string $description,
        public string $price,
        public string $currency,
        public bool $isActive,
        public bool $isFeatured,
        public int $sortOrder,
    ) {}
}

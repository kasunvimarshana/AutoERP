<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class UpdateStorefrontProductCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
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

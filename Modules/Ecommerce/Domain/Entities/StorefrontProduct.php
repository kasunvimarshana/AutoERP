<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Domain\Entities;

final class StorefrontProduct
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $slug,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $price,
        public readonly string $currency,
        public readonly bool $isActive,
        public readonly bool $isFeatured,
        public readonly int $sortOrder,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}

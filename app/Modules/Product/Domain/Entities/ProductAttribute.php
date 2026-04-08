<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use DateTimeInterface;

class ProductAttribute
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $name,
        public readonly array $values,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}

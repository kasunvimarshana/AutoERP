<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductCategory
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly ?string $parentId,
        public readonly string $code,
        public readonly string $name,
        public readonly bool $isActive,
    ) {}
}

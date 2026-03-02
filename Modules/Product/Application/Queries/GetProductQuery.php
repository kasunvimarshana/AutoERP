<?php

declare(strict_types=1);

namespace Modules\Product\Application\Queries;

final readonly class GetProductQuery
{
    public function __construct(
        public int $tenantId,
        public ?int $id = null,
        public ?string $sku = null,
        public ?int $categoryId = null,
        public ?int $brandId = null,
        public ?string $type = null,
        public bool $activeOnly = false,
        public int $page = 1,
        public int $perPage = 25,
        public string $sortBy = 'name',
        public string $sortDir = 'asc',
        public ?string $search = null,
    ) {}
}

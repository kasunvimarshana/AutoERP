<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductSearchProjectionRepositoryInterface
{
    public function rebuildForTenant(int $tenantId): int;

    public function rebuildForProduct(int $tenantId, int $productId): int;

    public function search(array $filters = []): LengthAwarePaginator;
}

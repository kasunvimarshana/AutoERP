<?php

namespace Modules\Manufacturing\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface BomRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;

    public function findActiveByProduct(string $tenantId, string $productId): ?object;
}

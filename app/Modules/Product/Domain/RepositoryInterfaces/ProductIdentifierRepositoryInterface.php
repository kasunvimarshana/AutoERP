<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductIdentifier;

interface ProductIdentifierRepositoryInterface extends RepositoryInterface
{
    public function save(ProductIdentifier $productIdentifier): ProductIdentifier;

    public function findByTenantAndValue(int $tenantId, string $value): ?ProductIdentifier;

    public function find($id, array $columns = ['*']): ?ProductIdentifier;
}

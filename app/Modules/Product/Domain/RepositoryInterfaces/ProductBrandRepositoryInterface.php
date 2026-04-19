<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductBrand;

interface ProductBrandRepositoryInterface extends RepositoryInterface
{
    public function save(ProductBrand $productBrand): ProductBrand;

    public function findByTenantAndCode(int $tenantId, string $code): ?ProductBrand;

    public function find(int|string $id, array $columns = ['*']): ?ProductBrand;
}

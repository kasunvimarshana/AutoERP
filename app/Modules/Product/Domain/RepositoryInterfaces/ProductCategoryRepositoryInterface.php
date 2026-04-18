<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductCategory;

interface ProductCategoryRepositoryInterface extends RepositoryInterface
{
    public function save(ProductCategory $productCategory): ProductCategory;

    public function findByTenantAndCode(int $tenantId, string $code): ?ProductCategory;

    public function find($id, array $columns = ['*']): ?ProductCategory;
}

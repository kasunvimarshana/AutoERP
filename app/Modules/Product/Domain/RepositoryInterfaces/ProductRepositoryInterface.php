<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\Product;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function save(Product $product): Product;

    public function findByTenantAndSku(int $tenantId, string $sku): ?Product;

    public function find(int|string $id, array $columns = ['*']): ?Product;
}

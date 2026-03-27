<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\Product;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findBySku(int $tenantId, string $sku): ?Product;

    public function findByTenant(int $tenantId): Collection;

    public function save(Product $product): Product;
}

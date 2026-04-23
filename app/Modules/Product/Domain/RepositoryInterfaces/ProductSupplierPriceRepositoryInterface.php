<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductSupplierPrice;

interface ProductSupplierPriceRepositoryInterface extends RepositoryInterface
{
    public function save(ProductSupplierPrice $productSupplierPrice): ProductSupplierPrice;

    public function find(int|string $id, array $columns = ['*']): ?ProductSupplierPrice;
}

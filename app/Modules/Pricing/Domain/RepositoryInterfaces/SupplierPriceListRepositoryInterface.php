<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Pricing\Domain\Entities\SupplierPriceList;

interface SupplierPriceListRepositoryInterface extends RepositoryInterface
{
    public function save(SupplierPriceList $supplierPriceList): SupplierPriceList;

    public function find(int|string $id, array $columns = ['*']): ?SupplierPriceList;
}

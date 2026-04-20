<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Pricing\Domain\Entities\CustomerPriceList;

interface CustomerPriceListRepositoryInterface extends RepositoryInterface
{
    public function save(CustomerPriceList $customerPriceList): CustomerPriceList;

    public function find(int|string $id, array $columns = ['*']): ?CustomerPriceList;
}

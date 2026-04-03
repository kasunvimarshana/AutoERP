<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\SalesOrder\Domain\Entities\SalesOrderLine;

interface SalesOrderLineRepositoryInterface extends RepositoryInterface
{
    public function save(SalesOrderLine $line): SalesOrderLine;
    public function findBySalesOrder(int $salesOrderId): Collection;
}

<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesOrders;

use Modules\Core\Application\Results\Result;

interface ListSalesOrdersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
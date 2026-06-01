<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesOrders;

use Modules\Core\Application\Results\Result;

interface GetSalesOrderServiceInterface
{
    public function execute(int|string $id): Result;
}

<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesOrders;

use Modules\Core\Application\Results\Result;

interface DeleteSalesOrderServiceInterface
{
    public function execute(int|string $id): Result;
}
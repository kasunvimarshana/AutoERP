<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesOrderLines;

use Modules\Core\Application\Results\Result;

interface DeleteSalesOrderLineServiceInterface
{
    public function execute(int|string $id): Result;
}
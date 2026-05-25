<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesReturns;

use Modules\Core\Application\Results\Result;

interface DeleteSalesReturnServiceInterface
{
    public function execute(int|string $id): Result;
}
<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Contracts\UseCases\SalesReturnLines;

use Modules\Core\Application\Results\Result;

interface GetSalesReturnLineServiceInterface
{
    public function execute(int|string $id): Result;
}

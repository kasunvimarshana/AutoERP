<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseReturns;

use Modules\Core\Application\Results\Result;

interface GetPurchaseReturnServiceInterface
{
    public function execute(int|string $id): Result;
}
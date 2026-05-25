<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseReturnLines;

use Modules\Core\Application\Results\Result;

interface GetPurchaseReturnLineServiceInterface
{
    public function execute(int|string $id): Result;
}
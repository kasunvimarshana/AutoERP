<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders;

use Modules\Core\Application\Results\Result;

interface DeletePurchaseOrderServiceInterface
{
    public function execute(int|string $id): Result;
}
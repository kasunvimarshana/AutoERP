<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseOrderLines;

use Modules\Core\Application\Results\Result;

interface DeletePurchaseOrderLineServiceInterface
{
    public function execute(int|string $id): Result;
}
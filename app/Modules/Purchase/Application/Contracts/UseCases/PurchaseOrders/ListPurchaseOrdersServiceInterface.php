<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Contracts\UseCases\PurchaseOrders;

use Modules\Core\Application\Results\Result;

interface ListPurchaseOrdersServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
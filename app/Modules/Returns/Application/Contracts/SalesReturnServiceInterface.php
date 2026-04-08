<?php

declare(strict_types=1);

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\SalesReturn;

interface SalesReturnServiceInterface
{
    public function getSalesReturn(string $tenantId, string $id): SalesReturn;

    public function getAllSalesReturns(string $tenantId): array;

    public function createSalesReturn(string $tenantId, array $data): SalesReturn;

    public function approveSalesReturn(string $tenantId, string $id): SalesReturn;

    public function completeSalesReturn(string $tenantId, string $id): SalesReturn;

    public function cancelSalesReturn(string $tenantId, string $id): SalesReturn;
}

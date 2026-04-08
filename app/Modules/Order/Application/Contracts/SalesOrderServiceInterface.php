<?php

declare(strict_types=1);

namespace Modules\Order\Application\Contracts;

use Modules\Order\Domain\Entities\SalesOrder;

interface SalesOrderServiceInterface
{
    public function getSalesOrder(string $tenantId, string $id): SalesOrder;

    /** @return SalesOrder[] */
    public function getAllSalesOrders(string $tenantId): array;

    public function createSalesOrder(string $tenantId, array $data): SalesOrder;

    public function confirmSalesOrder(string $tenantId, string $id): SalesOrder;

    public function cancelSalesOrder(string $tenantId, string $id): SalesOrder;

    public function updateSalesOrder(string $tenantId, string $id, array $data): SalesOrder;

    public function deleteSalesOrder(string $tenantId, string $id): void;
}

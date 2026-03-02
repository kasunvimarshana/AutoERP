<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Contracts;

use Modules\Sales\Domain\Entities\SalesOrder;

interface SalesOrderRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?SalesOrder;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function findByOrderNumber(string $orderNumber, int $tenantId): ?SalesOrder;

    public function save(SalesOrder $order): SalesOrder;

    public function delete(int $id, int $tenantId): void;

    public function nextOrderNumber(int $tenantId): string;
}

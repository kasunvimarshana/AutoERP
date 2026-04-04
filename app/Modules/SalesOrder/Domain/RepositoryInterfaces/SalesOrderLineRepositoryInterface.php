<?php

namespace Modules\SalesOrder\Domain\RepositoryInterfaces;

use Modules\SalesOrder\Domain\Entities\SalesOrderLine;

interface SalesOrderLineRepositoryInterface
{
    public function findById(int $id): ?SalesOrderLine;
    public function findBySalesOrder(int $salesOrderId): array;
    public function create(array $data): SalesOrderLine;
    public function update(SalesOrderLine $line, array $data): SalesOrderLine;
}

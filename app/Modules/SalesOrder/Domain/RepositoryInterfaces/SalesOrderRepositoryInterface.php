<?php

namespace Modules\SalesOrder\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\SalesOrder\Domain\Entities\SalesOrder;

interface SalesOrderRepositoryInterface
{
    public function findById(int $id): ?SalesOrder;
    public function findBySoNumber(int $tenantId, string $soNumber): ?SalesOrder;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): SalesOrder;
    public function update(SalesOrder $so, array $data): SalesOrder;
    public function save(SalesOrder $so): SalesOrder;
}

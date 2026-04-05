<?php
declare(strict_types=1);
namespace Modules\SalesOrder\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
interface SalesOrderRepositoryInterface {
    public function findById(int $id): ?SalesOrder;
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data, array $lines): SalesOrder;
    public function update(int $id, array $data): ?SalesOrder;
    public function updateStatus(int $id, string $status): bool;
    public function delete(int $id): bool;
}

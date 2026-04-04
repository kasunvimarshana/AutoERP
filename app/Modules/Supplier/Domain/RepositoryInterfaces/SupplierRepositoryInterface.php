<?php
namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Supplier\Domain\Entities\Supplier;

interface SupplierRepositoryInterface
{
    public function findById(int $id): ?Supplier;
    public function findByCode(int $tenantId, string $code): ?Supplier;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Supplier;
    public function update(Supplier $supplier, array $data): Supplier;
    public function delete(Supplier $supplier): bool;
}

<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Supplier\Domain\Entities\Supplier;

interface SupplierRepositoryInterface
{
    public function findById(int $id): ?Supplier;
    public function findByCode(int $tenantId, string $code): ?Supplier;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Supplier;
    public function update(int $id, array $data): ?Supplier;
    public function delete(int $id): bool;
}

<?php
declare(strict_types=1);
namespace Modules\Warehouse\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\Warehouse;
interface WarehouseRepositoryInterface {
    public function findById(int $id): ?Warehouse;
    public function findByCode(int $tenantId, string $code): ?Warehouse;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Warehouse;
    public function update(int $id, array $data): ?Warehouse;
    public function delete(int $id): bool;
}

<?php
declare(strict_types=1);
namespace Modules\Warehouse\Application\Contracts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\Warehouse;
interface WarehouseServiceInterface {
    public function findById(int $id): Warehouse;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Warehouse;
    public function update(int $id, array $data): Warehouse;
    public function delete(int $id): bool;
}

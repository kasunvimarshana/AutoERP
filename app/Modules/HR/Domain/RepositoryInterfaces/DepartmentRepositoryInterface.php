<?php
declare(strict_types=1);
namespace Modules\HR\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Department;

interface DepartmentRepositoryInterface
{
    public function findById(int $id): ?Department;
    public function findByCode(int $tenantId, string $code): ?Department;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findAllByTenant(int $tenantId): array;
    public function create(array $data): Department;
    public function update(int $id, array $data): ?Department;
    public function delete(int $id): bool;
}

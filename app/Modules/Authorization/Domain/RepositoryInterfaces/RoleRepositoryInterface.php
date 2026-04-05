<?php
declare(strict_types=1);
namespace Modules\Authorization\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Role;
    public function update(int $id, array $data): ?Role;
    public function delete(int $id): bool;
    public function syncPermissions(int $roleId, array $permissionIds): void;
    public function getPermissions(int $roleId): array;
}

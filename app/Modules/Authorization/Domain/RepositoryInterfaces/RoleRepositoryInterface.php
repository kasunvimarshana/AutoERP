<?php
namespace Modules\Authorization\Domain\RepositoryInterfaces;

use Modules\Authorization\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function findById(int $id): ?Role;
    public function findByName(int $tenantId, string $name): ?Role;
    public function findAll(int $tenantId, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): Role;
    public function delete(Role $role): bool;
    public function syncPermissions(Role $role, array $permissionIds): void;
    public function getPermissionIds(Role $role): array;
}

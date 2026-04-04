<?php
namespace Modules\Authorization\Domain\RepositoryInterfaces;

use Modules\Authorization\Domain\Entities\Permission;

interface PermissionRepositoryInterface
{
    public function findById(int $id): ?Permission;
    public function findByName(string $name): ?Permission;
    public function findAll(int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): Permission;
    public function delete(Permission $permission): bool;
}

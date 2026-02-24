<?php
namespace Modules\User\Domain\Contracts;
interface RoleRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByName(string $name, string $tenantId): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function assignPermission(string $roleId, string $permissionId): void;
    public function paginate(array $filters, int $perPage): object;
}

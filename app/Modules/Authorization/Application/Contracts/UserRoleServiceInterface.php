<?php
declare(strict_types=1);
namespace Modules\Authorization\Application\Contracts;

interface UserRoleServiceInterface
{
    public function getUserRoles(int $userId): array;
    public function assignRole(int $userId, int $roleId): void;
    public function removeRole(int $userId, int $roleId): void;
    public function syncRoles(int $userId, array $roleIds): void;
    public function userHasPermission(int $userId, string $permissionSlug): bool;
    public function userHasRole(int $userId, string $roleSlug): bool;
}

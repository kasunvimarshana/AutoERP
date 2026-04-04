<?php
namespace Modules\Authorization\Domain\RepositoryInterfaces;

use Modules\Authorization\Domain\Entities\UserRole;

interface UserRoleRepositoryInterface
{
    public function assign(int $userId, int $roleId): UserRole;
    public function revoke(int $userId, int $roleId): bool;
    public function getRoleIdsForUser(int $userId): array;
}

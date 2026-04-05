<?php
declare(strict_types=1);
namespace Modules\Authorization\Application\Services;

use Modules\Authorization\Application\Contracts\UserRoleServiceInterface;
use Modules\Authorization\Domain\RepositoryInterfaces\UserRoleRepositoryInterface;

class UserRoleService implements UserRoleServiceInterface
{
    public function __construct(private readonly UserRoleRepositoryInterface $repo) {}

    public function getUserRoles(int $userId): array
    {
        return $this->repo->getUserRoles($userId);
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $this->repo->assignRole($userId, $roleId);
    }

    public function removeRole(int $userId, int $roleId): void
    {
        $this->repo->removeRole($userId, $roleId);
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        $this->repo->syncRoles($userId, $roleIds);
    }

    public function userHasPermission(int $userId, string $permissionSlug): bool
    {
        return $this->repo->userHasPermission($userId, $permissionSlug);
    }

    public function userHasRole(int $userId, string $roleSlug): bool
    {
        return $this->repo->userHasRole($userId, $roleSlug);
    }
}

<?php

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\Role;

interface RoleRepositoryInterface extends RepositoryInterface
{
    public function findByName(int $tenantId, string $name): ?Role;
    public function syncPermissions(Role $role, array $permissionIds): void;
}

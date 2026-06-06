<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface RolePermissionRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantRolePermission(?int $tenantId, int $roleId, int $permissionId, ?int $excludeId = null): ?DataRecord;

    /**
     * @param  list<int>  $roleIds
     * @return list<string>
     */
    public function listPermissionNamesForTenantRoles(?int $tenantId, array $roleIds): array;
}

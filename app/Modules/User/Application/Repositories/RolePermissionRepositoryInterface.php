<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface RolePermissionRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantRolePermission(?int $tenantId, int $roleId, int $permissionId, ?int $excludeId = null): ?DataRecord;
}

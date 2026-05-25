<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface UserPermissionRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserPermission(?int $tenantId, int $userId, int $permissionId, ?int $excludeId = null): ?DataRecord;
}

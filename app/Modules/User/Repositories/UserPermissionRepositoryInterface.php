<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UserPermissionRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserPermission(int $tenantId, int $userId, int $permissionId, ?int $excludeId = null): ?DataRecord;

    /**
     * @return list<string>
     */
    public function listPermissionNamesForTenantUser(int $tenantId, int $userId): array;
}

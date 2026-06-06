<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UserRoleRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserRole(?int $tenantId, int $userId, int $roleId, ?int $excludeId = null): ?DataRecord;

    /**
     * @return list<array{id:int,name:string}>
     */
    public function listRoleSummariesForTenantUser(?int $tenantId, int $userId): array;
}

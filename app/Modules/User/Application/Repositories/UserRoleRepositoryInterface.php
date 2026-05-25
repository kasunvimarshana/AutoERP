<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface UserRoleRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserRole(?int $tenantId, int $userId, int $roleId, ?int $excludeId = null): ?DataRecord;
}

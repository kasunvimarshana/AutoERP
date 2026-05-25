<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface UserTenantRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantOrganizationUser(int $tenantId, ?int $organizationUnitId, int $userId, ?int $excludeId = null): ?DataRecord;

    public function clearDefaultForUser(int $tenantId, int $userId, ?int $excludeId = null): void;
}

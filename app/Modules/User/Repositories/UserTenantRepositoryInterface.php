<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface UserTenantRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantOrganizationUser(int $tenantId, ?int $organizationUnitId, int $userId, ?int $excludeId = null): ?DataRecord;

    public function existsForTenantAndUser(int $tenantId, int $userId): bool;

    public function existsForTenantUserAndOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId): bool;

    public function clearDefaultForUser(int $tenantId, int $userId, ?int $excludeId = null): void;
}

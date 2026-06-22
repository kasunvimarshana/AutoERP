<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UserTenantRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantOrganizationUser(int $tenantId, ?int $organizationUnitId, int $userId, ?int $excludeId = null): ?DataRecord;
    public function existsForTenantAndUser(int $tenantId, int $userId): bool;
    public function existsForTenantUserAndOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId): bool;
    /** @return list<DataRecord> */
    public function listDefaultsForTenantAndUser(int $tenantId, int $userId): array;
    public function clearDefaultForUser(int $tenantId, int $userId, ?int $excludeId = null): void;
}

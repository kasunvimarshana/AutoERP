<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UserOrganizationUnitRepositoryInterface extends RepositoryPortInterface
{
    public function findAssignment(
        int $tenantId,
        int $organizationUnitId,
        int $userId,
        ?int $excludeId = null,
    ): ?DataRecord;

    public function existsForTenantUserAndOrganizationUnit(
        int $tenantId,
        int $userId,
        int $organizationUnitId,
    ): bool;

    /** @return list<DataRecord> */
    public function listDefaultsForTenantAndUser(int $tenantId, int $userId): array;

    public function findDefaultForTenantAndUser(int $tenantId, int $userId): ?DataRecord;

    public function firstActiveForTenantAndUser(int $tenantId, int $userId): ?DataRecord;

    public function clearDefaultForUser(int $tenantId, int $userId, ?int $excludeId = null): void;

    public function setDefault(int $tenantId, int $userId, int $organizationUnitId): bool;

    public function deleteAssignment(int|string $id, int $tenantId, int $userId): bool;
}

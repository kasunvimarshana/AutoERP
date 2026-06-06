<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface OrganizationUnitSettingRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndOrganizationUnitAndGroupAndKey(
        int|string $tenantId,
        int|string $organizationUnitId,
        int|string $groupId,
        string $key,
    ): ?DataRecord;
}

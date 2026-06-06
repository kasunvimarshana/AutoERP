<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface OrganizationUnitSettingGroupRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndOrganizationUnitAndKey(
        int|string $tenantId,
        int|string $organizationUnitId,
        string $key,
    ): ?DataRecord;
}

<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface OrganizationUnitDocumentRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndOrganizationUnitAndName(
        int|string $tenantId,
        int|string $organizationUnitId,
        string $name,
    ): ?DataRecord;
}
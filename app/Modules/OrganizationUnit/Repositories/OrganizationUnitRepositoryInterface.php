<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface OrganizationUnitRepositoryInterface extends RepositoryPortInterface
{
    public function countByTenant(int $tenantId): int;

    public function pageByTenant(int $tenantId, array $criteria, int $perPage, int $page): PagedResult;

    /** @return list<DataRecord> */
    public function listAccessibleByIds(int $tenantId, array $organizationUnitIds): array;

    public function findByTenantAndCode(int $tenantId, string $code): ?DataRecord;

    public function lockActiveByTenantAndId(int $tenantId, int $organizationUnitId): ?DataRecord;
}

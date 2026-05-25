<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface OrganizationUnitRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord;

    public function findByTenantAndCode(int|string $tenantId, string $code): ?DataRecord;

    public function findByTenantAndPath(int|string $tenantId, string $path): ?DataRecord;
}

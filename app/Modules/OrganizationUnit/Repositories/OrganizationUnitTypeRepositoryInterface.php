<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface OrganizationUnitTypeRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord;
}

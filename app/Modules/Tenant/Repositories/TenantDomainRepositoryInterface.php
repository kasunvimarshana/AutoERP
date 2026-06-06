<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface TenantDomainRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByDomain(string $domain): ?DataRecord;

    public function findPrimaryByTenant(int|string $tenantId): ?DataRecord;

    public function clearPrimaryForTenant(int|string $tenantId): int;
}

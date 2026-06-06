<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface TenantSettingRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndKey(int|string $tenantId, string $key): ?DataRecord;
}

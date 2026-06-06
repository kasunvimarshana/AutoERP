<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface TenantDocumentRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord;
}

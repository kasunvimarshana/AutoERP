<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface TenantDocumentRepositoryInterface extends RepositoryPortInterface
{
    /**
     * @return list<DataRecord>
     */
    public function listByTenant(int|string $tenantId): array;

    public function findByTenantAndName(int|string $tenantId, string $name): ?DataRecord;
}

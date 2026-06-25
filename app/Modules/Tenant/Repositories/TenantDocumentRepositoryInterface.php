<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;

interface TenantDocumentRepositoryInterface
{
    /** @return list<DataRecord> */
    public function listByTenant(int $tenantId): array;
    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord;
    public function findByTenantAndName(int $tenantId, string $name): ?DataRecord;
    public function create(array $attributes): DataRecord;
    public function updateWithVersion(int|string $id, int $tenantId, int $expectedVersion, array $attributes): ?DataRecord;
    public function deleteWithVersion(int|string $id, int $tenantId, int $expectedVersion): bool;
}

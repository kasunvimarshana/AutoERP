<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface TenantDocumentRepositoryInterface
{
    public function pageByTenant(
        int $tenantId,
        ?string $documentType,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult;

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord;
    public function findByTenantAndName(int $tenantId, string $name): ?DataRecord;
    public function totalSizeByTenant(int $tenantId): int;
    public function create(array $attributes): DataRecord;
    public function updateWithVersion(int|string $id, int $tenantId, int $expectedVersion, array $attributes): ?DataRecord;
    public function deleteWithVersion(int|string $id, int $tenantId, int $expectedVersion): bool;
}

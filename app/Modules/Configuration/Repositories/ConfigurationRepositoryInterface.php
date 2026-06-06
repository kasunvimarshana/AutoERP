<?php

declare(strict_types=1);

namespace Modules\Configuration\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface ConfigurationRepositoryInterface extends RepositoryPortInterface
{
    public function findByKey(string $key): ?DataRecord;

    public function findByTenantAndKey(int $tenantId, string $key): ?DataRecord;

    public function findResolvedByScope(string $key, ?int $tenantId = null): ?DataRecord;

    public function pageByFilters(
        ?string $prefix,
        ?string $source,
        int $perPage,
        int $page,
        ?string $scope = null,
        ?int $tenantId = null,
    ): PagedResult;

    public function upsertScoped(string $key, array $attributes, ?int $tenantId = null): DataRecord;

    public function deleteScopedByKey(string $key, ?int $tenantId = null): bool;
}

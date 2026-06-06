<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface PermissionRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantNameGuard(?int $tenantId, string $name, string $guardName, ?int $excludeId = null): ?DataRecord;

    public function pageByFilters(?int $tenantId, ?string $module, ?string $search, int $perPage, int $page): PagedResult;
}

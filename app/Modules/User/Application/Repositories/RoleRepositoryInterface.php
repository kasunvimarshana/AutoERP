<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface RoleRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantNameGuard(?int $tenantId, string $name, string $guardName, ?int $excludeId = null): ?DataRecord;

    public function pageByFilters(?int $tenantId, ?string $search, int $perPage, int $page): PagedResult;
}

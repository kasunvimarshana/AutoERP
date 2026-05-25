<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface SystemUserRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantAndUserId(int $tenantId, int $userId): ?DataRecord;

    public function pageByFilters(
        ?int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        ?string $status,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult;
}

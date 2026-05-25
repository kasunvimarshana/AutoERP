<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface TenantPlanRepositoryInterface extends RepositoryPortInterface
{
    public function findBySlug(string $slug): ?DataRecord;

    public function pageByFilters(
        ?bool $isActive,
        ?string $billingInterval,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult;
}

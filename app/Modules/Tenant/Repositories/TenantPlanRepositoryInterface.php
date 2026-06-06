<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

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

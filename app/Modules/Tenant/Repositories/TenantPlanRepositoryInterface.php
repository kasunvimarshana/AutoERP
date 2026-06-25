<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface TenantPlanRepositoryInterface
{
    public function findById(int|string $id): ?DataRecord;
    public function findBySlug(string $slug): ?DataRecord;
    public function create(array $attributes): DataRecord;
    public function updateWithVersion(int|string $id, int $expectedVersion, array $attributes): ?DataRecord;
    public function pageByFilters(?bool $isActive, ?string $billingInterval, ?string $search, int $perPage, int $page): PagedResult;
    public function isAssigned(int|string $id): bool;
}

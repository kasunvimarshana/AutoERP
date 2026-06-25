<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface TenantPlanRepositoryInterface
{
    public function findById(int|string $id, bool $lockForUpdate = false): ?DataRecord;

    public function findBySlug(string $slug): ?DataRecord;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): DataRecord;

    /** @param array<string, mixed> $attributes */
    public function updateWithVersion(int|string $id, int $expectedVersion, array $attributes): ?DataRecord;

    public function pageByFilters(
        ?bool $isActive,
        ?string $billingInterval,
        ?string $search,
        int $perPage,
        int $page,
    ): PagedResult;

    public function hasCurrentAssignments(int|string $id): bool;
}

<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;

interface TenantPlanRevisionRepositoryInterface
{
    public function findById(int|string $id): ?DataRecord;

    public function findLatestByPlan(int|string $planId): ?DataRecord;

    /** @param array<string, mixed> $attributes */
    public function createNext(int|string $planId, array $attributes): DataRecord;
}

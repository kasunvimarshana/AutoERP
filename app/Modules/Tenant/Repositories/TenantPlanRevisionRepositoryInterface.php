<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Modules\Core\DTOs\DataRecord;

interface TenantPlanRevisionRepositoryInterface
{
    public function findById(int|string $id, bool $lockForUpdate = false): ?DataRecord;

    public function findLatestByPlan(int|string $planId): ?DataRecord;

    /** @return list<DataRecord> */
    public function listByPlan(int|string $planId): array;

    /** @param array<string, mixed> $attributes */
    public function createNext(int|string $planId, array $attributes): DataRecord;
}

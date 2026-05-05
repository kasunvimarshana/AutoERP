<?php

declare(strict_types=1);

namespace Modules\Service\Application\Contracts;

use Modules\Service\Domain\Entities\ServiceMaintenancePlan;

interface FindServiceMaintenancePlanServiceInterface
{
    public function findById(int $tenantId, int $id): ServiceMaintenancePlan;

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;
}

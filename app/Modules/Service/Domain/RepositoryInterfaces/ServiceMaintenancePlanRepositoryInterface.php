<?php

declare(strict_types=1);

namespace Modules\Service\Domain\RepositoryInterfaces;

use Modules\Service\Domain\Entities\ServiceMaintenancePlan;

interface ServiceMaintenancePlanRepositoryInterface
{
    public function save(ServiceMaintenancePlan $plan): ServiceMaintenancePlan;

    public function findById(int $tenantId, int $id): ?ServiceMaintenancePlan;

    public function findByCode(int $tenantId, string $planCode): ?ServiceMaintenancePlan;

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;

    public function existsByCode(int $tenantId, string $planCode): bool;
}

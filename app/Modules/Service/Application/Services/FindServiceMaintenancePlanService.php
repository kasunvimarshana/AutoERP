<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Service\Application\Contracts\FindServiceMaintenancePlanServiceInterface;
use Modules\Service\Domain\Entities\ServiceMaintenancePlan;
use Modules\Service\Domain\Exceptions\ServiceMaintenancePlanNotFoundException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceMaintenancePlanRepositoryInterface;

class FindServiceMaintenancePlanService implements FindServiceMaintenancePlanServiceInterface
{
    public function __construct(
        private readonly ServiceMaintenancePlanRepositoryInterface $planRepository,
    ) {}

    public function findById(int $tenantId, int $id): ServiceMaintenancePlan
    {
        $plan = $this->planRepository->findById($tenantId, $id);
        if ($plan === null) {
            throw new ServiceMaintenancePlanNotFoundException($id);
        }

        return $plan;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        return $this->planRepository->paginate($tenantId, $filters, $perPage, $page);
    }
}

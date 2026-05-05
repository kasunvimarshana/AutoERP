<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Service\Application\Contracts\CreateServiceMaintenancePlanServiceInterface;
use Modules\Service\Domain\Entities\ServiceMaintenancePlan;
use Modules\Service\Domain\RepositoryInterfaces\ServiceMaintenancePlanRepositoryInterface;

class CreateServiceMaintenancePlanService implements CreateServiceMaintenancePlanServiceInterface
{
    public function __construct(
        private readonly ServiceMaintenancePlanRepositoryInterface $planRepository,
    ) {}

    public function execute(array $data): ServiceMaintenancePlan
    {
        $plan = new ServiceMaintenancePlan(
            tenantId: (int) $data['tenant_id'],
            planCode: (string) $data['plan_code'],
            planName: (string) $data['plan_name'],
            triggerType: (string) $data['trigger_type'],
            status: $data['status'] ?? 'active',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            assetId: isset($data['asset_id']) ? (int) $data['asset_id'] : null,
            productId: isset($data['product_id']) ? (int) $data['product_id'] : null,
            assignedEmployeeId: isset($data['assigned_employee_id']) ? (int) $data['assigned_employee_id'] : null,
            description: $data['description'] ?? null,
            intervalDays: isset($data['interval_days']) ? (int) $data['interval_days'] : null,
            intervalKm: isset($data['interval_km']) ? (string) $data['interval_km'] : null,
            intervalHours: isset($data['interval_hours']) ? (string) $data['interval_hours'] : null,
            advanceNoticeDays: isset($data['advance_notice_days']) ? (int) $data['advance_notice_days'] : null,
            metadata: $data['metadata'] ?? null,
        );

        return DB::transaction(fn (): ServiceMaintenancePlan => $this->planRepository->save($plan));
    }
}

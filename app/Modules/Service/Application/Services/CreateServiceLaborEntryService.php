<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CreateServiceLaborEntryServiceInterface;
use Modules\Service\Domain\Entities\ServiceLaborEntry;
use Modules\Service\Domain\RepositoryInterfaces\ServiceLaborEntryRepositoryInterface;

class CreateServiceLaborEntryService extends BaseService implements CreateServiceLaborEntryServiceInterface
{
    public function __construct(private readonly ServiceLaborEntryRepositoryInterface $laborEntryRepository) {}

    protected function handle(array $data): ServiceLaborEntry
    {
        $hoursWorked = isset($data['hours_worked']) ? (float) $data['hours_worked'] : 0.0;
        $laborRate = isset($data['labor_rate']) ? (float) $data['labor_rate'] : 0.0;
        $commissionRate = isset($data['commission_rate']) ? (float) $data['commission_rate'] : 0.0;
        $laborAmount = $hoursWorked * $laborRate;
        $commissionAmount = $laborAmount * $commissionRate / 100.0;

        $entry = new ServiceLaborEntry(
            tenantId: (int) $data['tenant_id'],
            serviceWorkOrderId: (int) $data['service_work_order_id'],
            employeeId: (int) $data['employee_id'],
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            serviceTaskId: isset($data['service_task_id']) ? (int) $data['service_task_id'] : null,
            startedAt: $data['started_at'] ?? null,
            endedAt: $data['ended_at'] ?? null,
            hoursWorked: $hoursWorked,
            laborRate: $laborRate,
            laborAmount: $laborAmount,
            commissionRate: $commissionRate,
            commissionAmount: $commissionAmount,
            incentiveAmount: isset($data['incentive_amount']) ? (float) $data['incentive_amount'] : 0.0,
            status: 'draft',
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->laborEntryRepository->save($entry);
    }
}

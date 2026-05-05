<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\UpdateServiceLaborEntryServiceInterface;
use Modules\Service\Domain\Entities\ServiceLaborEntry;
use Modules\Service\Domain\RepositoryInterfaces\ServiceLaborEntryRepositoryInterface;
use RuntimeException;

class UpdateServiceLaborEntryService extends BaseService implements UpdateServiceLaborEntryServiceInterface
{
    public function __construct(private readonly ServiceLaborEntryRepositoryInterface $laborEntryRepository) {}

    protected function handle(array $data): ServiceLaborEntry
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->laborEntryRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new RuntimeException("Service labor entry {$id} not found.");
        }

        if ($existing->getStatus() === 'posted') {
            throw new RuntimeException('Cannot update a posted labor entry.');
        }

        $hoursWorked = isset($data['hours_worked']) ? (float) $data['hours_worked'] : $existing->getHoursWorked();
        $laborRate = isset($data['labor_rate']) ? (float) $data['labor_rate'] : $existing->getLaborRate();
        $commissionRate = isset($data['commission_rate']) ? (float) $data['commission_rate'] : $existing->getCommissionRate();
        $laborAmount = isset($data['labor_amount']) ? (float) $data['labor_amount'] : ($hoursWorked * $laborRate);
        $commissionAmount = isset($data['commission_amount']) ? (float) $data['commission_amount'] : ($laborAmount * $commissionRate / 100.0);

        $updated = new ServiceLaborEntry(
            tenantId: $existing->getTenantId(),
            serviceWorkOrderId: $existing->getServiceWorkOrderId(),
            employeeId: $existing->getEmployeeId(),
            orgUnitId: $existing->getOrgUnitId(),
            serviceTaskId: $existing->getServiceTaskId(),
            startedAt: $data['started_at'] ?? $existing->getStartedAt(),
            endedAt: $data['ended_at'] ?? $existing->getEndedAt(),
            hoursWorked: $hoursWorked,
            laborRate: $laborRate,
            laborAmount: $laborAmount,
            commissionRate: $commissionRate,
            commissionAmount: $commissionAmount,
            incentiveAmount: isset($data['incentive_amount']) ? (float) $data['incentive_amount'] : $existing->getIncentiveAmount(),
            status: $data['status'] ?? $existing->getStatus(),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->laborEntryRepository->save($updated);
    }
}

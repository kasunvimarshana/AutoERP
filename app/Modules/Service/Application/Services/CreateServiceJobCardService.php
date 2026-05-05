<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Service\Application\Contracts\CreateServiceJobCardServiceInterface;
use Modules\Service\Domain\Entities\ServiceJobCard;
use Modules\Service\Domain\RepositoryInterfaces\ServiceJobCardRepositoryInterface;

class CreateServiceJobCardService implements CreateServiceJobCardServiceInterface
{
    public function __construct(
        private readonly ServiceJobCardRepositoryInterface $jobCardRepository,
    ) {}

    public function execute(array $data): ServiceJobCard
    {
        $jobCard = new ServiceJobCard(
            tenantId: (int) $data['tenant_id'],
            jobNumber: (string) $data['job_number'],
            serviceType: (string) $data['service_type'],
            priority: (string) ($data['priority'] ?? 'normal'),
            status: $data['status'] ?? 'open',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            assetId: isset($data['asset_id']) ? (int) $data['asset_id'] : null,
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            maintenancePlanId: isset($data['maintenance_plan_id']) ? (int) $data['maintenance_plan_id'] : null,
            assignedTo: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            scheduledAt: isset($data['scheduled_at']) ? new \DateTimeImmutable($data['scheduled_at']) : null,
            odometerIn: isset($data['odometer_in']) ? (string) $data['odometer_in'] : null,
            isBillable: (bool) ($data['is_billable'] ?? true),
            diagnosis: $data['diagnosis'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );

        return DB::transaction(fn (): ServiceJobCard => $this->jobCardRepository->save($jobCard));
    }
}

<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CreateServiceWorkOrderServiceInterface;
use Modules\Service\Domain\Entities\ServiceWorkOrder;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;

class CreateServiceWorkOrderService extends BaseService implements CreateServiceWorkOrderServiceInterface
{
    public function __construct(
        private readonly ServiceWorkOrderRepositoryInterface $workOrderRepository,
        private readonly SyncAssetAvailabilityServiceInterface $syncAvailabilityService,
    ) {
        parent::__construct($workOrderRepository);
    }

    protected function handle(array $data): ServiceWorkOrder
    {
        $tenantId = (int) $data['tenant_id'];
        $orgUnitId = isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null;
        $assetId = (int) $data['asset_id'];
        $changedBy = isset($data['changed_by']) ? (int) $data['changed_by'] : null;

        $jobCardNumber = $this->workOrderRepository->nextJobCardNumber($tenantId, $orgUnitId);

        $workOrder = new ServiceWorkOrder(
            tenantId: $tenantId,
            assetId: $assetId,
            serviceType: (string) $data['service_type'],
            currencyId: (int) $data['currency_id'],
            billingMode: (string) ($data['billing_mode'] ?? 'customer_billable'),
            status: 'open',
            orgUnitId: $orgUnitId,
            jobCardNumber: $jobCardNumber,
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            openedBy: $changedBy,
            assignedTeamOrgUnitId: isset($data['assigned_team_org_unit_id']) ? (int) $data['assigned_team_org_unit_id'] : null,
            priority: $data['priority'] ?? 'normal',
            openedAt: $data['opened_at'] ?? now()->toISOString(),
            scheduledStartAt: $data['scheduled_start_at'] ?? null,
            scheduledEndAt: $data['scheduled_end_at'] ?? null,
            meterIn: isset($data['meter_in']) ? (float) $data['meter_in'] : null,
            meterUnit: $data['meter_unit'] ?? 'km',
            symptoms: $data['symptoms'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        $saved = $this->workOrderRepository->save($workOrder);

        $this->syncAvailabilityService->execute([
            'tenant_id' => $tenantId,
            'org_unit_id' => $orgUnitId,
            'asset_id' => $assetId,
            'target_status' => 'in_service',
            'reason_code' => 'service_work_order_opened',
            'source_type' => 'service_work_order',
            'source_id' => $saved->getId(),
            'changed_by' => $changedBy,
        ]);

        return $saved;
    }
}

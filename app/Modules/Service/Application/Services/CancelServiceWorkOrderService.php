<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CancelServiceWorkOrderServiceInterface;
use Modules\Service\Domain\Entities\ServiceWorkOrder;
use Modules\Service\Domain\Exceptions\ServiceWorkOrderException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;

class CancelServiceWorkOrderService extends BaseService implements CancelServiceWorkOrderServiceInterface
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
        $id = (int) $data['id'];
        $changedBy = isset($data['changed_by']) ? (int) $data['changed_by'] : null;

        $existing = $this->workOrderRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw ServiceWorkOrderException::notFound($id);
        }

        if (! $existing->isTransitionAllowed('cancelled')) {
            throw ServiceWorkOrderException::invalidTransition($existing->getStatus(), 'cancelled');
        }

        $cancelled = new ServiceWorkOrder(
            tenantId: $existing->getTenantId(),
            assetId: $existing->getAssetId(),
            serviceType: $existing->getServiceType(),
            currencyId: $existing->getCurrencyId(),
            billingMode: $existing->getBillingMode(),
            status: 'cancelled',
            orgUnitId: $existing->getOrgUnitId(),
            jobCardNumber: $existing->getJobCardNumber(),
            customerId: $existing->getCustomerId(),
            openedBy: $existing->getOpenedBy(),
            assignedTeamOrgUnitId: $existing->getAssignedTeamOrgUnitId(),
            priority: $existing->getPriority(),
            openedAt: $existing->getOpenedAt(),
            scheduledStartAt: $existing->getScheduledStartAt(),
            scheduledEndAt: $existing->getScheduledEndAt(),
            startedAt: $existing->getStartedAt(),
            meterIn: $existing->getMeterIn(),
            meterUnit: $existing->getMeterUnit(),
            symptoms: $existing->getSymptoms(),
            diagnosis: $existing->getDiagnosis(),
            notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->getNotes(),
            metadata: $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        $saved = $this->workOrderRepository->save($cancelled);

        $this->syncAvailabilityService->execute([
            'tenant_id' => $tenantId,
            'org_unit_id' => $existing->getOrgUnitId(),
            'asset_id' => $existing->getAssetId(),
            'target_status' => 'available',
            'reason_code' => 'service_work_order_cancelled',
            'source_type' => 'service_work_order',
            'source_id' => $id,
            'changed_by' => $changedBy,
        ]);

        return $saved;
    }
}

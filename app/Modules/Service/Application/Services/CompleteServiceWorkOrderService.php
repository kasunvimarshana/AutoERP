<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\CompleteServiceWorkOrderServiceInterface;
use Modules\Service\Domain\Entities\ServiceWorkOrder;
use Modules\Service\Domain\Exceptions\ServiceWorkOrderException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;

class CompleteServiceWorkOrderService extends BaseService implements CompleteServiceWorkOrderServiceInterface
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

        if (! $existing->isTransitionAllowed('completed')) {
            throw ServiceWorkOrderException::invalidTransition($existing->getStatus(), 'completed');
        }

        $completedAt = $data['completed_at'] ?? now()->toISOString();

        $completed = new ServiceWorkOrder(
            tenantId: $existing->getTenantId(),
            assetId: $existing->getAssetId(),
            serviceType: $existing->getServiceType(),
            currencyId: $existing->getCurrencyId(),
            billingMode: $existing->getBillingMode(),
            status: 'completed',
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
            completedAt: $completedAt,
            meterIn: $existing->getMeterIn(),
            meterOut: isset($data['meter_out']) ? (float) $data['meter_out'] : $existing->getMeterOut(),
            meterUnit: $existing->getMeterUnit(),
            symptoms: $existing->getSymptoms(),
            diagnosis: $existing->getDiagnosis(),
            resolution: $data['resolution'] ?? $existing->getResolution(),
            laborSubtotal: isset($data['labor_subtotal']) ? (float) $data['labor_subtotal'] : $existing->getLaborSubtotal(),
            partsSubtotal: isset($data['parts_subtotal']) ? (float) $data['parts_subtotal'] : $existing->getPartsSubtotal(),
            otherSubtotal: isset($data['other_subtotal']) ? (float) $data['other_subtotal'] : $existing->getOtherSubtotal(),
            taxTotal: isset($data['tax_total']) ? (float) $data['tax_total'] : $existing->getTaxTotal(),
            grandTotal: isset($data['grand_total']) ? (float) $data['grand_total'] : $existing->getGrandTotal(),
            notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->getNotes(),
            metadata: $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        $saved = $this->workOrderRepository->save($completed);

        $this->syncAvailabilityService->execute([
            'tenant_id' => $tenantId,
            'org_unit_id' => $existing->getOrgUnitId(),
            'asset_id' => $existing->getAssetId(),
            'target_status' => 'available',
            'reason_code' => 'service_work_order_completed',
            'source_type' => 'service_work_order',
            'source_id' => $id,
            'changed_by' => $changedBy,
        ]);

        return $saved;
    }
}

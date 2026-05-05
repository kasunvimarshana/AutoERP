<?php

declare(strict_types=1);

namespace Modules\Service\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Service\Application\Contracts\UpdateServiceWorkOrderServiceInterface;
use Modules\Service\Domain\Entities\ServiceWorkOrder;
use Modules\Service\Domain\Exceptions\ServiceWorkOrderException;
use Modules\Service\Domain\RepositoryInterfaces\ServiceWorkOrderRepositoryInterface;

class UpdateServiceWorkOrderService extends BaseService implements UpdateServiceWorkOrderServiceInterface
{
    public function __construct(private readonly ServiceWorkOrderRepositoryInterface $workOrderRepository)
    {
        parent::__construct($workOrderRepository);
    }

    protected function handle(array $data): ServiceWorkOrder
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->workOrderRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw ServiceWorkOrderException::notFound($id);
        }

        if (in_array($existing->getStatus(), ['completed', 'cancelled'], true)) {
            throw new \RuntimeException('Completed or cancelled work orders cannot be updated.');
        }

        $updated = new ServiceWorkOrder(
            tenantId: $existing->getTenantId(),
            assetId: $existing->getAssetId(),
            serviceType: $data['service_type'] ?? $existing->getServiceType(),
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : $existing->getCurrencyId(),
            billingMode: $data['billing_mode'] ?? $existing->getBillingMode(),
            status: $existing->getStatus(),
            orgUnitId: $existing->getOrgUnitId(),
            jobCardNumber: $existing->getJobCardNumber(),
            customerId: array_key_exists('customer_id', $data) ? (isset($data['customer_id']) ? (int) $data['customer_id'] : null) : $existing->getCustomerId(),
            openedBy: $existing->getOpenedBy(),
            assignedTeamOrgUnitId: array_key_exists('assigned_team_org_unit_id', $data) ? (isset($data['assigned_team_org_unit_id']) ? (int) $data['assigned_team_org_unit_id'] : null) : $existing->getAssignedTeamOrgUnitId(),
            priority: $data['priority'] ?? $existing->getPriority(),
            openedAt: $existing->getOpenedAt(),
            scheduledStartAt: array_key_exists('scheduled_start_at', $data) ? $data['scheduled_start_at'] : $existing->getScheduledStartAt(),
            scheduledEndAt: array_key_exists('scheduled_end_at', $data) ? $data['scheduled_end_at'] : $existing->getScheduledEndAt(),
            meterIn: isset($data['meter_in']) ? (float) $data['meter_in'] : $existing->getMeterIn(),
            meterUnit: $data['meter_unit'] ?? $existing->getMeterUnit(),
            symptoms: array_key_exists('symptoms', $data) ? $data['symptoms'] : $existing->getSymptoms(),
            diagnosis: array_key_exists('diagnosis', $data) ? $data['diagnosis'] : $existing->getDiagnosis(),
            resolution: array_key_exists('resolution', $data) ? $data['resolution'] : $existing->getResolution(),
            notes: array_key_exists('notes', $data) ? $data['notes'] : $existing->getNotes(),
            metadata: array_key_exists('metadata', $data) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->workOrderRepository->save($updated);
    }
}

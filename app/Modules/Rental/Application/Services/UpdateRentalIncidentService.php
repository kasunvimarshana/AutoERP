<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\UpdateRentalIncidentServiceInterface;
use Modules\Rental\Domain\Entities\RentalIncident;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;

class UpdateRentalIncidentService extends BaseService implements UpdateRentalIncidentServiceInterface
{
    public function __construct(private readonly RentalIncidentRepositoryInterface $incidentRepository) {}

    protected function handle(array $data): RentalIncident
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->incidentRepository->findById($tenantId, $id);
        if ($existing === null) {
            throw new \RuntimeException("Rental incident #{$id} not found.");
        }

        if (in_array($existing->getStatus(), ['resolved', 'waived'], true)) {
            throw new \RuntimeException('Resolved or waived incidents cannot be updated.');
        }

        $updated = new RentalIncident(
            tenantId: $existing->getTenantId(),
            rentalBookingId: $existing->getRentalBookingId(),
            assetId: $existing->getAssetId(),
            incidentType: $data['incident_type'] ?? $existing->getIncidentType(),
            status: $data['status'] ?? $existing->getStatus(),
            orgUnitId: $existing->getOrgUnitId(),
            occurredAt: $data['occurred_at'] ?? $existing->getOccurredAt(),
            reportedBy: $existing->getReportedBy(),
            description: array_key_exists('description', $data) ? $data['description'] : $existing->getDescription(),
            estimatedCost: isset($data['estimated_cost']) ? (float) $data['estimated_cost'] : $existing->getEstimatedCost(),
            recoveredAmount: isset($data['recovered_amount']) ? (float) $data['recovered_amount'] : $existing->getRecoveredAmount(),
            recoveryStatus: $data['recovery_status'] ?? $existing->getRecoveryStatus(),
            metadata: array_key_exists('metadata', $data) ? $data['metadata'] : $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $existing->getId(),
        );

        return $this->incidentRepository->save($updated);
    }
}

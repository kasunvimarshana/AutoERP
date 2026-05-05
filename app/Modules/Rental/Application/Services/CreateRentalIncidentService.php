<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CreateRentalIncidentServiceInterface;
use Modules\Rental\Domain\Entities\RentalIncident;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;

class CreateRentalIncidentService extends BaseService implements CreateRentalIncidentServiceInterface
{
    public function __construct(private readonly RentalIncidentRepositoryInterface $incidentRepository) {}

    protected function handle(array $data): RentalIncident
    {
        $incident = new RentalIncident(
            tenantId: (int) $data['tenant_id'],
            rentalBookingId: (int) $data['rental_booking_id'],
            assetId: (int) $data['asset_id'],
            incidentType: (string) $data['incident_type'],
            status: 'open',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            occurredAt: $data['occurred_at'] ?? now()->toISOString(),
            reportedBy: isset($data['reported_by']) ? (int) $data['reported_by'] : null,
            description: $data['description'] ?? null,
            estimatedCost: isset($data['estimated_cost']) ? (float) $data['estimated_cost'] : 0.0,
            recoveredAmount: 0.0,
            recoveryStatus: 'none',
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->incidentRepository->save($incident);
    }
}

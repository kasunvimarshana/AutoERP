<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\UpdateRentalInspectionServiceInterface;
use Modules\Rental\Domain\Entities\RentalInspection;
use Modules\Rental\Domain\RepositoryInterfaces\RentalInspectionRepositoryInterface;

class UpdateRentalInspectionService extends BaseService implements UpdateRentalInspectionServiceInterface
{
    public function __construct(private readonly RentalInspectionRepositoryInterface $inspectionRepository) {}

    protected function handle(array $data): RentalInspection
    {
        $tenantId = (int) $data['tenant_id'];
        $id = (int) $data['id'];

        $existing = $this->inspectionRepository->findById($tenantId, $id);

        if ($existing === null) {
            throw new \RuntimeException("Rental inspection {$id} not found.");
        }

        if ($existing->getInspectionStatus() === 'approved') {
            throw new \RuntimeException("Cannot update an approved inspection.");
        }

        $newStatus = $data['inspection_status'] ?? $existing->getInspectionStatus();

        // Auto-set inspectedAt when submitting
        $inspectedAt = $data['inspected_at'] ?? $existing->getInspectedAt();
        if ($newStatus === 'submitted' && $existing->getInspectionStatus() === 'draft' && $inspectedAt === null) {
            $inspectedAt = now()->toISOString();
        }

        $updated = new RentalInspection(
            tenantId: $tenantId,
            rentalBookingId: $existing->getRentalBookingId(),
            assetId: $existing->getAssetId(),
            inspectionType: $data['inspection_type'] ?? $existing->getInspectionType(),
            inspectionStatus: $newStatus,
            orgUnitId: $data['org_unit_id'] ?? $existing->getOrgUnitId(),
            inspectedBy: $data['inspected_by'] ?? $existing->getInspectedBy(),
            inspectedAt: $inspectedAt,
            meterReading: isset($data['meter_reading']) ? (float) $data['meter_reading'] : $existing->getMeterReading(),
            fuelLevelPercent: isset($data['fuel_level_percent']) ? (float) $data['fuel_level_percent'] : $existing->getFuelLevelPercent(),
            damageNotes: $data['damage_notes'] ?? $existing->getDamageNotes(),
            media: $data['media'] ?? $existing->getMedia(),
            metadata: $data['metadata'] ?? $existing->getMetadata(),
            rowVersion: $existing->getRowVersion() + 1,
            id: $id,
        );

        return $this->inspectionRepository->save($updated);
    }
}

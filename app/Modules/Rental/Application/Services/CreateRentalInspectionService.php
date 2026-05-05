<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\CreateRentalInspectionServiceInterface;
use Modules\Rental\Domain\Entities\RentalInspection;
use Modules\Rental\Domain\RepositoryInterfaces\RentalInspectionRepositoryInterface;

class CreateRentalInspectionService extends BaseService implements CreateRentalInspectionServiceInterface
{
    public function __construct(private readonly RentalInspectionRepositoryInterface $inspectionRepository) {}

    protected function handle(array $data): RentalInspection
    {
        $inspection = new RentalInspection(
            tenantId: (int) $data['tenant_id'],
            rentalBookingId: (int) $data['rental_booking_id'],
            assetId: (int) $data['asset_id'],
            inspectionType: (string) ($data['inspection_type'] ?? 'pickup'),
            inspectionStatus: 'draft',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            inspectedBy: isset($data['inspected_by']) ? (int) $data['inspected_by'] : null,
            inspectedAt: $data['inspected_at'] ?? null,
            meterReading: isset($data['meter_reading']) ? (float) $data['meter_reading'] : null,
            fuelLevelPercent: isset($data['fuel_level_percent']) ? (float) $data['fuel_level_percent'] : null,
            damageNotes: $data['damage_notes'] ?? null,
            media: is_array($data['media'] ?? null) ? $data['media'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->inspectionRepository->save($inspection);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalInspection;
use Modules\Rental\Domain\RepositoryInterfaces\RentalInspectionRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalInspectionModel;

class EloquentRentalInspectionRepository implements RentalInspectionRepositoryInterface
{
    public function __construct(private readonly RentalInspectionModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalInspection
    {
        /** @var RentalInspectionModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByBooking(int $tenantId, int $bookingId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('rental_booking_id', $bookingId)
            ->orderBy('id')
            ->get()
            ->map(fn (RentalInspectionModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalInspection $inspection): RentalInspection
    {
        $payload = [
            'tenant_id' => $inspection->getTenantId(),
            'org_unit_id' => $inspection->getOrgUnitId(),
            'row_version' => $inspection->getRowVersion(),
            'rental_booking_id' => $inspection->getRentalBookingId(),
            'asset_id' => $inspection->getAssetId(),
            'inspection_type' => $inspection->getInspectionType(),
            'inspection_status' => $inspection->getInspectionStatus(),
            'inspected_by' => $inspection->getInspectedBy(),
            'inspected_at' => $inspection->getInspectedAt(),
            'meter_reading' => $inspection->getMeterReading(),
            'fuel_level_percent' => $inspection->getFuelLevelPercent(),
            'damage_notes' => $inspection->getDamageNotes(),
            'media' => $inspection->getMedia(),
            'metadata' => $inspection->getMetadata(),
        ];

        $id = $inspection->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $inspection->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalInspectionModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $inspection->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalInspectionModel $saved */
            $saved = $this->model->newQuery()->create($payload);
        }

        return $this->mapModelToEntity($saved);
    }

    public function delete(int $tenantId, int $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function mapModelToEntity(RentalInspectionModel $model): RentalInspection
    {
        return new RentalInspection(
            tenantId: (int) $model->tenant_id,
            rentalBookingId: (int) $model->rental_booking_id,
            assetId: (int) $model->asset_id,
            inspectionType: (string) $model->inspection_type,
            inspectionStatus: (string) $model->inspection_status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            inspectedBy: $model->inspected_by !== null ? (int) $model->inspected_by : null,
            inspectedAt: $model->inspected_at !== null ? (string) $model->inspected_at : null,
            meterReading: $model->meter_reading !== null ? (float) $model->meter_reading : null,
            fuelLevelPercent: $model->fuel_level_percent !== null ? (float) $model->fuel_level_percent : null,
            damageNotes: $model->damage_notes,
            media: is_array($model->media) ? $model->media : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable($model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable($model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}

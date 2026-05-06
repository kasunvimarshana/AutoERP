<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalIncident;
use Modules\Rental\Domain\RepositoryInterfaces\RentalIncidentRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalIncidentModel;

class EloquentRentalIncidentRepository implements RentalIncidentRepositoryInterface
{
    public function __construct(private readonly RentalIncidentModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalIncident
    {
        /** @var RentalIncidentModel|null $model */
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
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn (RentalIncidentModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function findByTenant(int $tenantId, ?int $orgUnitId = null, array $filters = []): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        if ($orgUnitId !== null) {
            $query->where('org_unit_id', $orgUnitId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['incident_type'])) {
            $query->where('incident_type', $filters['incident_type']);
        }

        if (! empty($filters['asset_id'])) {
            $query->where('asset_id', (int) $filters['asset_id']);
        }

        return $query->orderByDesc('occurred_at')
            ->get()
            ->map(fn (RentalIncidentModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalIncident $incident): RentalIncident
    {
        $payload = [
            'tenant_id' => $incident->getTenantId(),
            'org_unit_id' => $incident->getOrgUnitId(),
            'row_version' => $incident->getRowVersion(),
            'rental_booking_id' => $incident->getRentalBookingId(),
            'asset_id' => $incident->getAssetId(),
            'incident_type' => $incident->getIncidentType(),
            'status' => $incident->getStatus(),
            'occurred_at' => $incident->getOccurredAt(),
            'reported_by' => $incident->getReportedBy(),
            'description' => $incident->getDescription(),
            'estimated_cost' => $incident->getEstimatedCost(),
            'recovered_amount' => $incident->getRecoveredAmount(),
            'recovery_status' => $incident->getRecoveryStatus(),
            'metadata' => $incident->getMetadata(),
        ];

        $id = $incident->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $incident->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalIncidentModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $incident->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalIncidentModel $saved */
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

    private function mapModelToEntity(RentalIncidentModel $model): RentalIncident
    {
        return new RentalIncident(
            tenantId: (int) $model->tenant_id,
            rentalBookingId: (int) $model->rental_booking_id,
            assetId: (int) $model->asset_id,
            incidentType: (string) $model->incident_type,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            occurredAt: $model->occurred_at !== null ? (string) $model->occurred_at : null,
            reportedBy: $model->reported_by !== null ? (int) $model->reported_by : null,
            description: $model->description,
            estimatedCost: (float) $model->estimated_cost,
            recoveredAmount: (float) $model->recovered_amount,
            recoveryStatus: (string) $model->recovery_status,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            id: (int) $model->id,
        );
    }
}

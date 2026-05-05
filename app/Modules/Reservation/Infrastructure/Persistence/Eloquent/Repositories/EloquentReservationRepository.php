<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Reservation\Domain\Entities\Reservation;
use Modules\Reservation\Domain\RepositoryInterfaces\ReservationRepositoryInterface;
use Modules\Reservation\Domain\ValueObjects\ReservationStatus;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\Models\ReservationModel;

class EloquentReservationRepository implements ReservationRepositoryInterface
{
    public function findById(string $id): ?Reservation
    {
        $model = ReservationModel::withoutGlobalScope('tenant')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(string $tenantId, string $orgUnitId): array
    {
        return ReservationModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReservationModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findByVehicle(string $tenantId, string $vehicleId): array
    {
        return ReservationModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('reserved_from')
            ->get()
            ->map(fn (ReservationModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(Reservation $reservation): Reservation
    {
        $model = ReservationModel::withoutGlobalScope('tenant')->firstOrNew(['id' => $reservation->id]);

        $model->fill([
            'id' => $reservation->id,
            'tenant_id' => $reservation->tenantId,
            'org_unit_id' => $reservation->orgUnitId,
            'row_version' => $reservation->rowVersion,
            'reservation_number' => $reservation->reservationNumber,
            'vehicle_id' => $reservation->vehicleId,
            'customer_id' => $reservation->customerId,
            'reserved_from' => $reservation->reservedFrom->format('Y-m-d H:i:s'),
            'reserved_to' => $reservation->reservedTo->format('Y-m-d H:i:s'),
            'status' => $reservation->status->value,
            'estimated_amount' => $reservation->estimatedAmount,
            'currency' => $reservation->currency,
            'notes' => $reservation->notes,
            'metadata' => $reservation->metadata,
            'is_active' => $reservation->isActive,
        ]);

        $model->save();

        return $this->toEntity($model->fresh());
    }

    public function updateStatus(string $id, string $status): Reservation
    {
        $model = ReservationModel::withoutGlobalScope('tenant')->findOrFail($id);
        $model->update(['status' => $status]);
        $model->increment('row_version');

        return $this->toEntity($model->fresh());
    }

    public function delete(string $id): void
    {
        ReservationModel::withoutGlobalScope('tenant')->where('id', $id)->delete();
    }

    private function toEntity(ReservationModel $model): Reservation
    {
        return new Reservation(
            id: $model->id,
            tenantId: (string) $model->tenant_id,
            orgUnitId: (string) $model->org_unit_id,
            rowVersion: (int) $model->row_version,
            reservationNumber: $model->reservation_number,
            vehicleId: $model->vehicle_id,
            customerId: $model->customer_id,
            reservedFrom: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->reserved_from->format('Y-m-d H:i:s')),
            reservedTo: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->reserved_to->format('Y-m-d H:i:s')),
            status: ReservationStatus::from($model->status),
            estimatedAmount: (string) $model->estimated_amount,
            currency: $model->currency,
            notes: $model->notes,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            createdAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->created_at->format('Y-m-d H:i:s')),
            updatedAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->updated_at->format('Y-m-d H:i:s')),
        );
    }
}

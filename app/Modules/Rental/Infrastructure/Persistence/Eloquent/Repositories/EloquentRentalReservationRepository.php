<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalReservation;
use Modules\Rental\Domain\RepositoryInterfaces\RentalReservationRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalReservationModel;

class EloquentRentalReservationRepository implements RentalReservationRepositoryInterface
{
    public function create(RentalReservation $reservation): void
    {
        RentalReservationModel::create([
            'id' => $reservation->getId(),
            'tenant_id' => $reservation->getTenantId(),
            'vehicle_id' => $reservation->getVehicleId(),
            'customer_id' => $reservation->getCustomerId(),
            'driver_id' => $reservation->getDriverId(),
            'reservation_number' => $reservation->getReservationNumber(),
            'start_at' => $reservation->getStartAt(),
            'expected_return_at' => $reservation->getExpectedReturnAt(),
            'billing_unit' => $reservation->getBillingUnit(),
            'base_rate' => $reservation->getBaseRate(),
            'estimated_distance' => $reservation->getEstimatedDistance(),
            'estimated_amount' => $reservation->getEstimatedAmount(),
            'status' => $reservation->getStatus(),
            'version' => $reservation->getVersion(),
            'notes' => $reservation->getNotes(),
        ]);
    }

    public function findById(string $id): ?RentalReservation
    {
        $model = RentalReservationModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByReservationNumber(string $tenantId, string $reservationNumber): ?RentalReservation
    {
        $model = RentalReservationModel::byTenant($tenantId)
            ->where('reservation_number', $reservationNumber)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getByStatus(string $tenantId, string $status, int $page = 1, int $limit = 50): array
    {
        $query = RentalReservationModel::byTenant($tenantId)->byStatus($status);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn ($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function hasVehicleConflict(string $tenantId, string $vehicleId, \DateTime $startAt, \DateTime $endAt): bool
    {
        return RentalReservationModel::byTenant($tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', ['draft', 'confirmed'])
            ->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('start_at', [$startAt, $endAt])
                    ->orWhereBetween('expected_return_at', [$startAt, $endAt])
                    ->orWhere(function ($nested) use ($startAt, $endAt) {
                        $nested->where('start_at', '<=', $startAt)
                            ->where('expected_return_at', '>=', $endAt);
                    });
            })
            ->exists();
    }

    public function update(RentalReservation $reservation): void
    {
        RentalReservationModel::findOrFail($reservation->getId())->update([
            'driver_id' => $reservation->getDriverId(),
            'start_at' => $reservation->getStartAt(),
            'expected_return_at' => $reservation->getExpectedReturnAt(),
            'billing_unit' => $reservation->getBillingUnit(),
            'base_rate' => $reservation->getBaseRate(),
            'estimated_distance' => $reservation->getEstimatedDistance(),
            'estimated_amount' => $reservation->getEstimatedAmount(),
            'status' => $reservation->getStatus(),
            'version' => $reservation->getVersion(),
            'notes' => $reservation->getNotes(),
        ]);
    }

    private function toDomain(RentalReservationModel $model): RentalReservation
    {
        return new RentalReservation(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            vehicleId: (string) $model->vehicle_id,
            customerId: (string) $model->customer_id,
            driverId: $model->driver_id !== null ? (string) $model->driver_id : null,
            reservationNumber: (string) $model->reservation_number,
            startAt: $model->start_at,
            expectedReturnAt: $model->expected_return_at,
            billingUnit: (string) $model->billing_unit,
            baseRate: (string) $model->base_rate,
            estimatedDistance: (string) $model->estimated_distance,
            estimatedAmount: (string) $model->estimated_amount,
            status: (string) $model->status,
            version: (int) $model->version,
            notes: $model->notes,
        );
    }
}

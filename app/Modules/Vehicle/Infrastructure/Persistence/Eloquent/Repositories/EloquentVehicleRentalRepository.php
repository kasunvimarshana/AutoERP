<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Vehicle\Domain\Entities\VehicleRental;
use Modules\Vehicle\Domain\RepositoryInterfaces\VehicleRentalRepositoryInterface;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleRentalModel;

class EloquentVehicleRentalRepository implements VehicleRentalRepositoryInterface
{
    public function __construct(private readonly VehicleRentalModel $rentalModel) {}

    public function create(array $data): VehicleRental
    {
        /** @var VehicleRentalModel $record */
        $record = $this->rentalModel->newQuery()->create([
            'tenant_id' => $data['tenant_id'],
            'vehicle_id' => $data['vehicle_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'assigned_driver_id' => $data['assigned_driver_id'] ?? null,
            'rental_no' => $data['rental_no'],
            'rental_status' => $data['rental_status'] ?? 'reserved',
            'pricing_model' => $data['pricing_model'],
            'base_rate' => $data['base_rate'],
            'distance_km' => $data['distance_km'] ?? '0.000000',
            'included_km' => $data['included_km'] ?? '0.000000',
            'extra_km_rate' => $data['extra_km_rate'] ?? '0.000000',
            'subtotal' => $data['subtotal'],
            'tax_amount' => $data['tax_amount'],
            'grand_total' => $data['grand_total'],
            'reserved_from' => $data['reserved_from'] ?? null,
            'reserved_until' => $data['reserved_until'] ?? null,
            'odometer_out' => $data['odometer_out'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        return $this->toEntity($record);
    }

    public function find(int $tenantId, int $rentalId): ?VehicleRental
    {
        /** @var VehicleRentalModel|null $record */
        $record = $this->rentalModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $rentalId)
            ->first();

        return $record !== null ? $this->toEntity($record) : null;
    }

    public function paginate(int $tenantId, int $vehicleId, int $perPage, int $page): mixed
    {
        return $this->rentalModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function markStatus(int $tenantId, int $rentalId, string $status, array $extra = []): bool
    {
        $payload = [
            'rental_status' => $status,
            'updated_at' => now(),
        ];

        if ($status === 'active') {
            $payload['rented_out_at'] = $extra['rented_out_at'] ?? now();
        }

        if ($status === 'completed') {
            $payload['returned_at'] = $extra['returned_at'] ?? now();
            $payload['odometer_in'] = $extra['odometer_in'] ?? null;
        }

        return $this->rentalModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $rentalId)
            ->update($payload) > 0;
    }

    private function toEntity(VehicleRentalModel $model): VehicleRental
    {
        return new VehicleRental(
            tenantId: (int) $model->tenant_id,
            vehicleId: (int) $model->vehicle_id,
            rentalNo: (string) $model->rental_no,
            rentalStatus: (string) $model->rental_status,
            pricingModel: (string) $model->pricing_model,
            grandTotal: (string) $model->grand_total,
            id: (int) $model->id,
        );
    }
}

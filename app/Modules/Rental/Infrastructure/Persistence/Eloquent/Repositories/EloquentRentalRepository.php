<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Rental\Domain\Entities\Rental;
use Modules\Rental\Domain\RepositoryInterfaces\RentalRepositoryInterface;
use Modules\Rental\Domain\ValueObjects\RentalStatus;
use Modules\Rental\Domain\ValueObjects\RentalType;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalModel;

class EloquentRentalRepository implements RentalRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Rental
    {
        $model = RentalModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $query = RentalModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->orderByDesc('id')->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function findActiveByVehicle(int $vehicleId, int $tenantId): array
    {
        return RentalModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', [RentalStatus::Active->value, RentalStatus::Confirmed->value])
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function findByCustomer(int $customerId, int $tenantId): array
    {
        return RentalModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(Rental $rental): Rental
    {
        $data = [
            'tenant_id'            => $rental->tenantId,
            'org_unit_id'          => $rental->orgUnitId,
            'customer_id'          => $rental->customerId,
            'vehicle_id'           => $rental->vehicleId,
            'driver_id'            => $rental->driverId,
            'rental_number'        => $rental->rentalNumber,
            'rental_type'          => $rental->rentalType->value,
            'status'               => $rental->status->value,
            'pickup_location'      => $rental->pickupLocation,
            'return_location'      => $rental->returnLocation,
            'scheduled_start_at'   => $rental->scheduledStartAt->format('Y-m-d H:i:s'),
            'scheduled_end_at'     => $rental->scheduledEndAt->format('Y-m-d H:i:s'),
            'actual_start_at'      => $rental->actualStartAt?->format('Y-m-d H:i:s'),
            'actual_end_at'        => $rental->actualEndAt?->format('Y-m-d H:i:s'),
            'start_odometer'       => $rental->startOdometer,
            'end_odometer'         => $rental->endOdometer,
            'rate_per_day'         => $rental->ratePerDay,
            'estimated_days'       => $rental->estimatedDays,
            'actual_days'          => $rental->actualDays,
            'subtotal'             => $rental->subtotal,
            'discount_amount'      => $rental->discountAmount,
            'tax_amount'           => $rental->taxAmount,
            'total_amount'         => $rental->totalAmount,
            'deposit_amount'       => $rental->depositAmount,
            'notes'                => $rental->notes,
            'cancelled_at'         => $rental->cancelledAt?->format('Y-m-d H:i:s'),
            'cancellation_reason'  => $rental->cancellationReason,
            'metadata'             => $rental->metadata,
            'is_active'            => $rental->isActive,
        ];

        if ($rental->id === null) {
            $data['row_version'] = 1;
            $model = RentalModel::create($data);
        } else {
            $model = RentalModel::withoutGlobalScope('tenant')->findOrFail($rental->id);
            $model->update($data);
            $model->increment('row_version');
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function updateStatus(int $id, int $tenantId, RentalStatus $status, array $extraData = []): void
    {
        $data = array_merge(['status' => $status->value], $extraData);
        RentalModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->update($data);
    }

    public function delete(int $id, int $tenantId): void
    {
        RentalModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->delete();
    }

    private function toEntity(RentalModel $model): Rental
    {
        return new Rental(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            customerId: (int) $model->customer_id,
            vehicleId: (int) $model->vehicle_id,
            driverId: $model->driver_id !== null ? (int) $model->driver_id : null,
            rentalNumber: $model->rental_number,
            rentalType: RentalType::from($model->rental_type),
            status: RentalStatus::from($model->status),
            pickupLocation: $model->pickup_location,
            returnLocation: $model->return_location,
            scheduledStartAt: new DateTimeImmutable($model->scheduled_start_at->format('Y-m-d H:i:s')),
            scheduledEndAt: new DateTimeImmutable($model->scheduled_end_at->format('Y-m-d H:i:s')),
            actualStartAt: $model->actual_start_at !== null ? new DateTimeImmutable($model->actual_start_at->format('Y-m-d H:i:s')) : null,
            actualEndAt: $model->actual_end_at !== null ? new DateTimeImmutable($model->actual_end_at->format('Y-m-d H:i:s')) : null,
            startOdometer: $model->start_odometer !== null ? (string) $model->start_odometer : null,
            endOdometer: $model->end_odometer !== null ? (string) $model->end_odometer : null,
            ratePerDay: (string) $model->rate_per_day,
            estimatedDays: (string) $model->estimated_days,
            actualDays: $model->actual_days !== null ? (string) $model->actual_days : null,
            subtotal: (string) $model->subtotal,
            discountAmount: (string) $model->discount_amount,
            taxAmount: (string) $model->tax_amount,
            totalAmount: (string) $model->total_amount,
            depositAmount: (string) $model->deposit_amount,
            notes: $model->notes,
            cancelledAt: $model->cancelled_at !== null ? new DateTimeImmutable($model->cancelled_at->format('Y-m-d H:i:s')) : null,
            cancellationReason: $model->cancellation_reason,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            rowVersion: (int) $model->row_version,
        );
    }
}

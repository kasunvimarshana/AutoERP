<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleOwnershipData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\Vehicle\Validators\VehicleValidationService;

final class VehicleOwnershipService
{
    public function __construct(private readonly VehicleValidationService $validator) {}

    public function assign(Vehicle $vehicle, VehicleOwnershipData $data): VehicleOwnership
    {
        $this->validator->validateOwnership($vehicle, $data);

        return DB::transaction(function () use ($vehicle, $data): VehicleOwnership {
            $ownerType = (string) $data->ownerType;
            $this->lockVehicle($vehicle);
            $this->lockOwnerType($vehicle, $ownerType);
            if ($data->isCurrent) {
                $this->closeCurrentOwnerships($vehicle, $ownerType);
            }

            $ownership = $vehicle->ownerships()->create([
                'tenant_id' => $vehicle->tenant_id,
                'organization_unit_id' => $vehicle->organization_unit_id,
                'owner_type' => $ownerType,
                'owner_id' => $data->ownerId,
                'ownership_type' => $data->ownershipType,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'notes' => $data->notes,
            ]);

            $this->assertSingleCurrent($vehicle, $ownerType);

            return $ownership->refresh()->load(['customerOwner', 'supplierOwner']);
        });
    }

    public function update(Vehicle $vehicle, VehicleOwnership $ownership, VehicleOwnershipData $data): VehicleOwnership
    {
        $this->assertOwned($vehicle, $ownership);
        $this->validator->validateOwnership($vehicle, $data);

        return DB::transaction(function () use ($vehicle, $ownership, $data): VehicleOwnership {
            $ownerType = (string) $data->ownerType;
            $this->lockVehicle($vehicle);
            $ownership = VehicleOwnership::query()->lockForUpdate()->findOrFail($ownership->getKey());
            $this->assertOwned($vehicle, $ownership);
            $this->lockOwnerType($vehicle, $ownerType);

            if ($data->isCurrent) {
                $this->closeCurrentOwnerships($vehicle, $ownerType, $ownership);
            }
            $ownership->fill([
                'owner_type' => $ownerType,
                'owner_id' => $data->ownerId,
                'ownership_type' => $data->ownershipType,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'notes' => $data->notes,
            ])->save();

            $this->assertSingleCurrent($vehicle, $ownerType);

            return $ownership->refresh()->load(['customerOwner', 'supplierOwner']);
        });
    }

    public function delete(Vehicle $vehicle, VehicleOwnership $ownership): void
    {
        $this->assertOwned($vehicle, $ownership);
        if ((bool) $ownership->is_current) {
            throw new InvalidArgumentException('Current vehicle ownership cannot be deleted. Assign a new current ownership first.');
        }
        $ownership->delete();
    }

    /** @param list<VehicleOwnershipData> $ownerships */
    public function replace(Vehicle $vehicle, array $ownerships): void
    {
        DB::transaction(function () use ($vehicle, $ownerships): void {
            foreach ($ownerships as $ownership) {
                $this->assign($vehicle, $ownership);
            }
        });
    }

    private function assertOwned(Vehicle $vehicle, VehicleOwnership $ownership): void
    {
        if ((int) $ownership->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle ownership does not belong to the vehicle.');
        }
    }

    private function lockVehicle(Vehicle $vehicle): void
    {
        Vehicle::query()->whereKey($vehicle->getKey())->lockForUpdate()->firstOrFail();
    }

    private function lockOwnerType(Vehicle $vehicle, string $ownerType): void
    {
        $vehicle->ownerships()
            ->where('owner_type', $ownerType)
            ->lockForUpdate()
            ->get();
    }

    private function closeCurrentOwnerships(Vehicle $vehicle, string $ownerType, ?VehicleOwnership $except = null): void
    {
        $query = $vehicle->ownerships()
            ->where('owner_type', $ownerType)
            ->where('is_current', true);
        if ($except !== null) {
            $query->whereKeyNot($except->getKey());
        }
        $query->lockForUpdate()->get();
        $query->update(['is_current' => false, 'ended_at' => now()]);
    }

    private function assertSingleCurrent(Vehicle $vehicle, string $ownerType): void
    {
        $currentRows = $vehicle->ownerships()
            ->where('owner_type', $ownerType)
            ->where('is_current', true)
            ->count();

        if ($currentRows > 1) {
            throw new InvalidArgumentException('Vehicle can only have one current ownership per owner type.');
        }
    }
}

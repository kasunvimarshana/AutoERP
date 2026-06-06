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
            if ($data->isCurrent) {
                $vehicle->ownerships()->where('is_current', true)->update(['is_current' => false, 'ended_at' => now()]);
            }

            $ownership = $vehicle->ownerships()->create([
                'tenant_id' => $vehicle->tenant_id,
                'organization_unit_id' => $vehicle->organization_unit_id,
                'owner_type' => $data->ownerType,
                'owner_id' => $data->ownerId,
                'customer_id' => $data->customerId,
                'ownership_type' => $data->ownershipType,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'notes' => $data->notes,
            ]);

            if ($data->isCurrent) {
                $vehicle->fill([
                    'customer_id' => $data->customerId,
                    'current_owner_type' => $data->ownerType,
                    'current_owner_id' => $data->ownerId,
                ])->save();
            }

            return $ownership->refresh()->load('customer');
        });
    }

    public function update(Vehicle $vehicle, VehicleOwnership $ownership, VehicleOwnershipData $data): VehicleOwnership
    {
        $this->assertOwned($vehicle, $ownership);
        $this->validator->validateOwnership($vehicle, $data);

        return DB::transaction(function () use ($vehicle, $ownership, $data): VehicleOwnership {
            if ($data->isCurrent) {
                $vehicle->ownerships()->whereKeyNot($ownership->getKey())->where('is_current', true)->update(['is_current' => false, 'ended_at' => now()]);
            }
            $ownership->fill([
                'owner_type' => $data->ownerType,
                'owner_id' => $data->ownerId,
                'customer_id' => $data->customerId,
                'ownership_type' => $data->ownershipType,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'notes' => $data->notes,
            ])->save();
            if ($data->isCurrent) {
                $vehicle->fill(['customer_id' => $data->customerId, 'current_owner_type' => $data->ownerType, 'current_owner_id' => $data->ownerId])->save();
            }
            return $ownership->refresh()->load('customer');
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
        $vehicle->ownerships()->delete();
        foreach ($ownerships as $ownership) { $this->assign($vehicle, $ownership); }
    }

    private function assertOwned(Vehicle $vehicle, VehicleOwnership $ownership): void
    {
        if ((int) $ownership->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle ownership does not belong to the vehicle.');
        }
    }
}

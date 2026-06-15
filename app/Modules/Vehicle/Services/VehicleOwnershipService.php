<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Carbon\CarbonImmutable;
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
        $owner = $this->validator->validateOwnership($vehicle, $data);
        $this->assertCurrentTransitionDate($vehicle, $data);

        return DB::transaction(function () use ($vehicle, $data, $owner): VehicleOwnership {
            if ($data->isCurrent) {
                $vehicle->ownerships()->where('is_current', true)->update([
                    'is_current' => false,
                    'ended_at' => $data->startedAt,
                ]);
            }

            $ownership = $vehicle->ownerships()->create([
                'tenant_id' => $vehicle->tenant_id,
                'organization_unit_id' => $vehicle->organization_unit_id,
                'owner_type' => $owner['owner_type'],
                'owner_id' => $owner['owner_id'],
                'customer_id' => $owner['customer_id'],
                'ownership_type' => $data->ownershipType,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'notes' => $data->notes,
            ]);

            if ($data->isCurrent) {
                $vehicle->fill([
                    'customer_id' => $owner['customer_id'],
                    'current_owner_type' => $owner['owner_type'],
                    'current_owner_id' => $owner['owner_id'],
                ])->save();
            }

            return $ownership->refresh()->load(['customer', 'supplier']);
        });
    }

    public function update(Vehicle $vehicle, VehicleOwnership $ownership, VehicleOwnershipData $data): VehicleOwnership
    {
        $this->assertOwned($vehicle, $ownership);
        $owner = $this->validator->validateOwnership($vehicle, $data);
        $this->assertCurrentTransitionDate($vehicle, $data, (int) $ownership->getKey());
        $wasCurrent = (bool) $ownership->is_current;
        $currentOwnerType = $ownership->owner_type instanceof \BackedEnum
            ? (string) $ownership->owner_type->value
            : (string) $ownership->owner_type;
        $currentOwnershipType = $ownership->ownership_type instanceof \BackedEnum
            ? (string) $ownership->ownership_type->value
            : (string) $ownership->ownership_type;
        if ($wasCurrent && $data->isCurrent && (
            $currentOwnerType !== $owner['owner_type']
            || $ownership->owner_id !== $owner['owner_id']
            || $ownership->customer_id !== $owner['customer_id']
            || $currentOwnershipType !== $data->ownershipType->value
        )) {
            return $this->assign($vehicle, $data);
        }

        return DB::transaction(function () use ($vehicle, $ownership, $data, $owner, $wasCurrent): VehicleOwnership {
            if ($data->isCurrent) {
                $vehicle->ownerships()->whereKeyNot($ownership->getKey())->where('is_current', true)->update([
                    'is_current' => false,
                    'ended_at' => $data->startedAt,
                ]);
            }
            $ownership->fill([
                'owner_type' => $owner['owner_type'],
                'owner_id' => $owner['owner_id'],
                'customer_id' => $owner['customer_id'],
                'ownership_type' => $data->ownershipType,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'notes' => $data->notes,
            ])->save();
            if ($data->isCurrent) {
                $vehicle->fill([
                    'customer_id' => $owner['customer_id'],
                    'current_owner_type' => $owner['owner_type'],
                    'current_owner_id' => $owner['owner_id'],
                ])->save();
            } elseif ($wasCurrent) {
                $vehicle->fill([
                    'customer_id' => null,
                    'current_owner_type' => null,
                    'current_owner_id' => null,
                ])->save();
            }

            return $ownership->refresh()->load(['customer', 'supplier']);
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
        $vehicle->fill([
            'customer_id' => null,
            'current_owner_type' => null,
            'current_owner_id' => null,
        ])->save();
        foreach ($ownerships as $ownership) {
            $this->assign($vehicle, $ownership);
        }
    }

    private function assertOwned(Vehicle $vehicle, VehicleOwnership $ownership): void
    {
        if ((int) $ownership->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle ownership does not belong to the vehicle.');
        }
    }

    private function assertCurrentTransitionDate(
        Vehicle $vehicle,
        VehicleOwnershipData $data,
        ?int $ignoreOwnershipId = null,
    ): void {
        if (! $data->isCurrent) {
            return;
        }

        $current = $vehicle->ownerships()
            ->when($ignoreOwnershipId !== null, fn ($query) => $query->whereKeyNot($ignoreOwnershipId))
            ->where('is_current', true)
            ->first();
        if ($current?->started_at !== null
            && CarbonImmutable::parse($data->startedAt)->isBefore($current->started_at)) {
            throw new InvalidArgumentException('New current ownership cannot start before the existing current ownership.');
        }
    }
}

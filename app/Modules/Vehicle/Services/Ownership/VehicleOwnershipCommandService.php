<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services\Ownership;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Data\CreateVehicleOwnershipData;
use Modules\Vehicle\Data\VersionedVehicleOwnershipCommand;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleOwnership;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleOwnershipCommandService
{
    public function __construct(private readonly VehicleOwnerResolverRegistry $owners) {}

    public function create(CreateVehicleOwnershipData $data, int $tenantId, ?int $organizationUnitId): VehicleOwnership
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId): VehicleOwnership {
            $vehicle = $this->lockedVehicle($data->vehicleId, $tenantId, $organizationUnitId);
            $snapshot = $this->owners->resolve($data->ownerType, $tenantId, $organizationUnitId, $data->ownerId);
            $start = CarbonImmutable::parse($data->startedAt);
            $end = $data->endedAt === null ? null : CarbonImmutable::parse($data->endedAt);
            $this->assertPeriod($start, $end, $data->isCurrent);
            $this->lockVehicleOwnerships((int) $vehicle->getKey());

            $activePair = VehicleOwnership::query()
                ->where('vehicle_id', $vehicle->getKey())
                ->where('owner_key', $snapshot->key)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();
            if ($activePair !== null) {
                throw new ConflictHttpException('An active relationship already exists for this vehicle and owner.');
            }

            if ($data->isCurrent) {
                $this->closeCurrent((int) $vehicle->getKey(), $data->ownerType->value, $start);
            }

            $ownership = new VehicleOwnership();
            $ownership->forceFill([
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'vehicle_id' => $vehicle->getKey(),
                'owner_type' => $snapshot->type,
                'owner_id' => $snapshot->id,
                'owner_key' => $snapshot->key,
                'owner_code_snapshot' => $snapshot->code,
                'owner_name_snapshot' => $snapshot->name,
                'ownership_type' => $data->ownershipType,
                'started_at' => $start,
                'ended_at' => $end,
                'is_current' => $data->isCurrent,
                'current_guard' => $data->isCurrent ? 1 : null,
                'active_guard' => $end === null ? 1 : null,
                'notes' => $data->notes,
            ])->save();

            return $this->load($ownership);
        }, 3);
    }

    public function updateNotes(VehicleOwnership $ownership, VersionedVehicleOwnershipCommand $command): VehicleOwnership
    {
        return DB::transaction(function () use ($ownership, $command): VehicleOwnership {
            $locked = $this->lockOwnership($ownership, $command->expectedVersion);
            $locked->forceFill([
                'notes' => $command->notes,
                'row_version' => $locked->row_version + 1,
            ])->save();

            return $this->load($locked);
        }, 3);
    }

    public function setCurrent(VehicleOwnership $ownership, VersionedVehicleOwnershipCommand $command): VehicleOwnership
    {
        return DB::transaction(function () use ($ownership, $command): VehicleOwnership {
            $locked = $this->lockOwnership($ownership, $command->expectedVersion);
            if ($locked->ended_at !== null) {
                throw new ConflictHttpException('An ended ownership relationship cannot become current.');
            }
            $this->lockedVehicle((int) $locked->vehicle_id, (int) $locked->tenant_id, $locked->organization_unit_id);
            $this->lockVehicleOwnerships((int) $locked->vehicle_id);
            $this->closeCurrent(
                (int) $locked->vehicle_id,
                $locked->owner_type->value,
                CarbonImmutable::parse($locked->started_at),
                (int) $locked->getKey(),
            );
            $locked->forceFill([
                'is_current' => true,
                'current_guard' => 1,
                'row_version' => $locked->row_version + 1,
            ])->save();

            return $this->load($locked);
        }, 3);
    }

    public function clearCurrent(VehicleOwnership $ownership, VersionedVehicleOwnershipCommand $command): VehicleOwnership
    {
        return DB::transaction(function () use ($ownership, $command): VehicleOwnership {
            $locked = $this->lockOwnership($ownership, $command->expectedVersion);
            $locked->forceFill([
                'is_current' => false,
                'current_guard' => null,
                'row_version' => $locked->row_version + 1,
            ])->save();

            return $this->load($locked);
        }, 3);
    }

    public function end(VehicleOwnership $ownership, VersionedVehicleOwnershipCommand $command): VehicleOwnership
    {
        return DB::transaction(function () use ($ownership, $command): VehicleOwnership {
            $locked = $this->lockOwnership($ownership, $command->expectedVersion);
            if ($locked->ended_at !== null) {
                throw new ConflictHttpException('This ownership relationship has already ended.');
            }
            $endedAt = CarbonImmutable::parse($command->endedAt ?? now()->toDateTimeString());
            $this->assertPeriod(CarbonImmutable::parse($locked->started_at), $endedAt, false);
            $locked->forceFill([
                'ended_at' => $endedAt,
                'is_current' => false,
                'current_guard' => null,
                'active_guard' => null,
                'row_version' => $locked->row_version + 1,
            ])->save();

            return $this->load($locked);
        }, 3);
    }

    private function closeCurrent(int $vehicleId, string $ownerType, CarbonImmutable $replacementStart, ?int $exceptId = null): void
    {
        $query = VehicleOwnership::query()
            ->where('vehicle_id', $vehicleId)
            ->where('owner_type', $ownerType)
            ->where('is_current', true);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        /** @var VehicleOwnership|null $current */
        $current = $query->lockForUpdate()->first();
        if ($current === null) {
            return;
        }
        if ($replacementStart->lessThanOrEqualTo(CarbonImmutable::parse($current->started_at))) {
            throw new ConflictHttpException('A replacement ownership must start after the current ownership started.');
        }
        $current->forceFill([
            'ended_at' => $replacementStart,
            'is_current' => false,
            'current_guard' => null,
            'active_guard' => null,
            'row_version' => $current->row_version + 1,
        ])->save();
    }

    private function lockOwnership(VehicleOwnership $ownership, int $expectedVersion): VehicleOwnership
    {
        /** @var VehicleOwnership $locked */
        $locked = VehicleOwnership::query()->lockForUpdate()->findOrFail($ownership->getKey());
        if ((int) $locked->row_version !== $expectedVersion) {
            throw new ConflictHttpException('Vehicle ownership was changed by another request. Reload and try again.');
        }

        return $locked;
    }

    private function lockedVehicle(int $vehicleId, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::query()
            ->withTrashed()
            ->whereKey($vehicleId)
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->lockForUpdate()
            ->firstOrFail();

        return $vehicle;
    }

    private function lockVehicleOwnerships(int $vehicleId): void
    {
        VehicleOwnership::query()->where('vehicle_id', $vehicleId)->orderBy('id')->lockForUpdate()->get();
    }

    private function assertPeriod(CarbonImmutable $start, ?CarbonImmutable $end, bool $current): void
    {
        if ($end !== null && $end->lessThanOrEqualTo($start)) {
            throw new ConflictHttpException('Ownership end date must be after its start date.');
        }
        if ($current && $end !== null) {
            throw new ConflictHttpException('An ended ownership relationship cannot be current.');
        }
    }

    private function load(VehicleOwnership $ownership): VehicleOwnership
    {
        return $ownership->refresh()->load(['vehicle.make', 'vehicle.model', 'organizationUnit']);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleOwnershipData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleOwnership;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class VehicleOwnershipService
{
    private const ACTIVE_GUARD = 1;
    private const CURRENT_GUARD = 1;

    public function __construct(private readonly VehicleOwnerDirectory $owners) {}

    public function assign(Vehicle $vehicle, VehicleOwnershipData $data, ?int $actorId = null): VehicleOwnership
    {
        return DB::transaction(function () use ($vehicle, $data, $actorId): VehicleOwnership {
            $vehicle = Vehicle::query()->whereKey($vehicle->getKey())->lockForUpdate()->firstOrFail();
            $this->lockOwnerships($vehicle);
            $this->validateData($data);
            $snapshot = $this->owners->resolve(
                $data->ownerType,
                $data->ownerId,
                (int) $vehicle->tenant_id,
                $vehicle->organization_unit_id === null ? null : (int) $vehicle->organization_unit_id,
            );

            $this->assertNoOverlap($vehicle, $data);

            $ownership = VehicleOwnership::query()->forceCreate([
                'tenant_id' => (int) $vehicle->tenant_id,
                'organization_unit_id' => $vehicle->organization_unit_id,
                'vehicle_id' => (int) $vehicle->getKey(),
                'owner_type' => $snapshot->type->value,
                'owner_id' => $snapshot->id,
                'owner_scope_key' => $snapshot->scopeKey(),
                'owner_code_snapshot' => $snapshot->code,
                'owner_name_snapshot' => $snapshot->name,
                'ownership_type' => $data->ownershipType->value,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'current_guard' => $data->isCurrent ? self::CURRENT_GUARD : null,
                'active_guard' => $data->endedAt === null ? self::ACTIVE_GUARD : null,
                'notes' => $data->notes,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            return $this->loaded($ownership);
        }, 3);
    }

    public function supersede(
        VehicleOwnership $ownership,
        VehicleOwnershipData $data,
        int $expectedVersion,
        string $reason,
        ?int $actorId = null,
    ): VehicleOwnership {
        return DB::transaction(function () use ($ownership, $data, $expectedVersion, $reason, $actorId): VehicleOwnership {
            $ownership = VehicleOwnership::query()->lockForUpdate()->findOrFail($ownership->getKey());
            $this->assertVersion($ownership, $expectedVersion);
            if ($ownership->ended_at !== null) {
                throw new ConflictHttpException('Ended vehicle ownership history cannot be superseded. Create a new relationship instead.');
            }
            $vehicle = Vehicle::query()->whereKey($ownership->vehicle_id)->lockForUpdate()->firstOrFail();
            $this->lockOwnerships($vehicle);
            $this->validateData($data);
            $replacementStart = CarbonImmutable::parse($data->startedAt);
            if ($replacementStart->lessThan(CarbonImmutable::parse($ownership->started_at))) {
                throw new ConflictHttpException('A superseding ownership cannot start before the current revision.');
            }

            $snapshot = $this->owners->resolve(
                $data->ownerType,
                $data->ownerId,
                (int) $ownership->tenant_id,
                $ownership->organization_unit_id === null ? null : (int) $ownership->organization_unit_id,
            );

            $this->close($ownership, $data->startedAt, $actorId);
            $this->assertNoOverlap($vehicle, $data, (int) $ownership->getKey());

            $replacement = VehicleOwnership::query()->forceCreate([
                'tenant_id' => (int) $ownership->tenant_id,
                'organization_unit_id' => $ownership->organization_unit_id,
                'vehicle_id' => (int) $ownership->vehicle_id,
                'owner_type' => $snapshot->type->value,
                'owner_id' => $snapshot->id,
                'owner_scope_key' => $snapshot->scopeKey(),
                'owner_code_snapshot' => $snapshot->code,
                'owner_name_snapshot' => $snapshot->name,
                'ownership_type' => $data->ownershipType->value,
                'started_at' => $data->startedAt,
                'ended_at' => $data->endedAt,
                'is_current' => $data->isCurrent,
                'current_guard' => $data->isCurrent ? self::CURRENT_GUARD : null,
                'active_guard' => $data->endedAt === null ? self::ACTIVE_GUARD : null,
                'supersedes_ownership_id' => (int) $ownership->getKey(),
                'correction_reason' => trim($reason),
                'notes' => $data->notes,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            return $this->loaded($replacement);
        }, 3);
    }

    public function setCurrent(VehicleOwnership $ownership, int $expectedVersion, ?int $actorId = null): VehicleOwnership
    {
        return DB::transaction(function () use ($ownership, $expectedVersion, $actorId): VehicleOwnership {
            $ownership = VehicleOwnership::query()->lockForUpdate()->findOrFail($ownership->getKey());
            $this->assertVersion($ownership, $expectedVersion);
            if ($ownership->ended_at !== null) {
                throw new ConflictHttpException('Ended vehicle ownership history cannot be made current.');
            }
            $vehicle = Vehicle::query()->whereKey($ownership->vehicle_id)->lockForUpdate()->firstOrFail();
            $this->lockOwnerships($vehicle);
            VehicleOwnership::query()
                ->where('vehicle_id', $vehicle->getKey())
                ->where('owner_type', $ownership->owner_type->value)
                ->where('is_current', true)
                ->whereKeyNot($ownership->getKey())
                ->update([
                    'is_current' => false,
                    'current_guard' => null,
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_by' => $actorId,
                    'updated_at' => now(),
                ]);
            $ownership->forceFill([
                'is_current' => true,
                'current_guard' => self::CURRENT_GUARD,
                'row_version' => (int) $ownership->row_version + 1,
                'updated_by' => $actorId,
            ])->save();

            return $this->loaded($ownership);
        }, 3);
    }

    public function clearCurrent(VehicleOwnership $ownership, int $expectedVersion, ?int $actorId = null): VehicleOwnership
    {
        return DB::transaction(function () use ($ownership, $expectedVersion, $actorId): VehicleOwnership {
            $ownership = VehicleOwnership::query()->lockForUpdate()->findOrFail($ownership->getKey());
            $this->assertVersion($ownership, $expectedVersion);
            $ownership->forceFill([
                'is_current' => false,
                'current_guard' => null,
                'row_version' => (int) $ownership->row_version + 1,
                'updated_by' => $actorId,
            ])->save();

            return $this->loaded($ownership);
        }, 3);
    }

    public function end(
        VehicleOwnership $ownership,
        int $expectedVersion,
        string $endedAt,
        ?int $actorId = null,
    ): VehicleOwnership {
        return DB::transaction(function () use ($ownership, $expectedVersion, $endedAt, $actorId): VehicleOwnership {
            $ownership = VehicleOwnership::query()->lockForUpdate()->findOrFail($ownership->getKey());
            $this->assertVersion($ownership, $expectedVersion);
            if ($ownership->ended_at !== null) {
                throw new ConflictHttpException('Vehicle ownership relationship has already ended.');
            }
            $this->validatePeriod((string) $ownership->started_at, $endedAt, false);
            $this->close($ownership, $endedAt, $actorId);

            return $this->loaded($ownership);
        }, 3);
    }

    /** @param list<VehicleOwnershipData> $ownerships */
    public function assignMany(Vehicle $vehicle, array $ownerships, ?int $actorId = null): void
    {
        foreach ($ownerships as $ownership) {
            $this->assign($vehicle, $ownership, $actorId);
        }
    }

    private function close(VehicleOwnership $ownership, string $endedAt, ?int $actorId): void
    {
        $ownership->forceFill([
            'ended_at' => $endedAt,
            'is_current' => false,
            'current_guard' => null,
            'active_guard' => null,
            'row_version' => (int) $ownership->row_version + 1,
            'updated_by' => $actorId,
        ])->save();
    }

    private function assertNoOverlap(Vehicle $vehicle, VehicleOwnershipData $data, ?int $excludeId = null): void
    {
        $start = CarbonImmutable::parse($data->startedAt);
        $end = $data->endedAt === null ? null : CarbonImmutable::parse($data->endedAt);
        $query = VehicleOwnership::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->where('owner_type', $data->ownerType->value)
            ->where('started_at', '<', $end ?? CarbonImmutable::parse('9999-12-31 23:59:59'))
            ->where(function (Builder $scope) use ($start): void {
                $scope->whereNull('ended_at')->orWhere('ended_at', '>', $start);
            });
        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }
        if ($query->exists()) {
            throw new ConflictHttpException('Vehicle ownership periods cannot overlap for the same owner type.');
        }
    }

    private function validateData(VehicleOwnershipData $data): void
    {
        $this->validatePeriod($data->startedAt, $data->endedAt, $data->isCurrent);
        if (! $data->ownerType->allows($data->ownershipType)) {
            throw new InvalidArgumentException('Ownership type is not valid for the selected owner type.');
        }
    }

    private function validatePeriod(string $startedAt, ?string $endedAt, bool $isCurrent): void
    {
        $start = CarbonImmutable::parse($startedAt);
        if ($endedAt !== null && CarbonImmutable::parse($endedAt)->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Vehicle ownership end date must be after its start date.');
        }
        if ($isCurrent && $endedAt !== null) {
            throw new InvalidArgumentException('An ended vehicle ownership cannot be current.');
        }
    }

    private function assertVersion(VehicleOwnership $ownership, int $expectedVersion): void
    {
        if ($expectedVersion < 1 || (int) $ownership->row_version !== $expectedVersion) {
            throw new ConflictHttpException('Vehicle ownership changed after it was loaded. Refresh and try again.');
        }
    }

    private function lockOwnerships(Vehicle $vehicle): void
    {
        VehicleOwnership::query()->where('vehicle_id', $vehicle->getKey())->orderBy('id')->lockForUpdate()->get();
    }

    private function loaded(VehicleOwnership $ownership): VehicleOwnership
    {
        return $ownership->refresh()->load(['vehicle.make', 'vehicle.model']);
    }
}

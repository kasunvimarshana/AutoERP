<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierVehicle;
use Modules\Vehicle\Models\Vehicle;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class SupplierVehicleService
{
    public function create(array $data, int $tenantId, ?int $organizationUnitId): SupplierVehicle
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId) {
            $vehicle = $this->vehicle((int) $data['vehicle_id'], $tenantId, $organizationUnitId);
            $supplier = $this->supplier((int) $data['supplier_id'], $tenantId, $organizationUnitId);
            $this->lockRows((int) $vehicle->getKey());
            $this->validateDates((string) $data['started_at'], $data['ended_at'] ?? null, (bool) ($data['is_current'] ?? false));
            $active = SupplierVehicle::query()->where('vehicle_id', $vehicle->getKey())->where('supplier_id', $supplier->getKey())->whereNull('ended_at')->lockForUpdate()->first();
            if ($active && ! ($data['is_current'] ?? false)) {
                throw new ConflictHttpException('An active Supplier-Vehicle relationship already exists.');
            }$r = $active ?? new SupplierVehicle;
            $r->forceFill(['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId, 'supplier_id' => $supplier->getKey(), 'vehicle_id' => $vehicle->getKey(), 'relationship_type' => $data['relationship_type'] ?? 'third_party', 'started_at' => $active?->started_at ?? $data['started_at'], 'ended_at' => $data['ended_at'] ?? null, 'active_guard' => ($data['ended_at'] ?? null) === null ? 1 : null, 'notes' => $data['notes'] ?? $active?->notes, 'is_current' => false, 'current_guard' => null])->save();
            if ($data['is_current'] ?? false) {
                $this->setCurrentLocked($r);
            }

            return $this->loaded($r);
        }, 3);
    }

    public function update(SupplierVehicle $r, array $data): SupplierVehicle
    {
        return DB::transaction(function () use ($r, $data) {
            $this->vehicle((int) $r->vehicle_id, (int) $r->tenant_id, $r->organization_unit_id);
            $this->lockRows((int) $r->vehicle_id);
            $r = SupplierVehicle::query()->lockForUpdate()->findOrFail($r->getKey());
            $started = (string) ($data['started_at'] ?? $r->started_at);
            $ended = array_key_exists('ended_at', $data) ? $data['ended_at'] : $r->ended_at;
            if ($r->ended_at !== null && array_key_exists('ended_at', $data) && $ended === null) {
                throw new ConflictHttpException('Ended Supplier-Vehicle relationships cannot be reopened. Create a new relationship instead.');
            }$this->validateDates($started, $ended, (bool) $r->is_current);
            $attrs = array_intersect_key($data, array_flip(['relationship_type', 'started_at', 'ended_at', 'notes']));
            if ($ended !== null) {
                $attrs += ['is_current' => false, 'current_guard' => null, 'active_guard' => null];
            }$r->forceFill($attrs)->save();

            return $this->loaded($r);
        }, 3);
    }

    public function setCurrent(SupplierVehicle $r): SupplierVehicle
    {
        return DB::transaction(function () use ($r) {
            $this->vehicle((int) $r->vehicle_id, (int) $r->tenant_id, $r->organization_unit_id);
            $this->lockRows((int) $r->vehicle_id);
            $r = SupplierVehicle::query()->lockForUpdate()->findOrFail($r->getKey());
            if ($r->ended_at !== null) {
                throw new ConflictHttpException('An ended Supplier-Vehicle relationship cannot be current.');
            }$this->supplier((int) $r->supplier_id, (int) $r->tenant_id, $r->organization_unit_id);
            $this->setCurrentLocked($r);

            return $this->loaded($r);
        }, 3);
    }

    public function clearCurrent(SupplierVehicle $r): SupplierVehicle
    {
        return DB::transaction(function () use ($r) {
            $this->vehicle((int) $r->vehicle_id, (int) $r->tenant_id, $r->organization_unit_id);
            $this->lockRows((int) $r->vehicle_id);
            $r = SupplierVehicle::query()->lockForUpdate()->findOrFail($r->getKey());
            $r->forceFill(['is_current' => false, 'current_guard' => null])->save();

            return $this->loaded($r);
        }, 3);
    }

    public function end(SupplierVehicle $r, ?string $endedAt = null): SupplierVehicle
    {
        return DB::transaction(function () use ($r, $endedAt) {
            $this->vehicle((int) $r->vehicle_id, (int) $r->tenant_id, $r->organization_unit_id);
            $this->lockRows((int) $r->vehicle_id);
            $r = SupplierVehicle::query()->lockForUpdate()->findOrFail($r->getKey());
            $endedAt ??= now()->toDateTimeString();
            $this->validateDates((string) $r->started_at, $endedAt, false);
            $r->forceFill(['ended_at' => $endedAt, 'is_current' => false, 'current_guard' => null, 'active_guard' => null])->save();

            return $this->loaded($r);
        }, 3);
    }

    private function setCurrentLocked(SupplierVehicle $r): void
    {
        SupplierVehicle::query()->where('vehicle_id', $r->vehicle_id)->where('is_current', true)->whereKeyNot($r->getKey())->update(['is_current' => false, 'current_guard' => null, 'updated_at' => now()]);
        $r->forceFill(['is_current' => true, 'current_guard' => 1, 'active_guard' => 1, 'ended_at' => null])->save();
        if (SupplierVehicle::query()->where('vehicle_id', $r->vehicle_id)->where('is_current', true)->count() !== 1) {
            throw new \LogicException('Supplier current relationship invariant failed.');
        }
    }

    private function lockRows(int $vehicleId): void
    {
        SupplierVehicle::query()->where('vehicle_id', $vehicleId)->orderBy('id')->lockForUpdate()->get();
    }

    private function vehicle(int $id, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        return Vehicle::query()->whereKey($id)->where('tenant_id', $tenantId)->where(fn (Builder $q): Builder => $organizationUnitId === null ? $q->whereNull('organization_unit_id') : $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))->lockForUpdate()->firstOrFail();
    }

    private function supplier(int $id, int $tenantId, ?int $organizationUnitId): Supplier
    {
        $s = Supplier::query()->whereKey($id)->where('tenant_id', $tenantId)->where(fn (Builder $q): Builder => $organizationUnitId === null ? $q->whereNull('organization_unit_id') : $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))->firstOrFail();
        if ($s->status !== SupplierStatus::Active) {
            throw new ConflictHttpException('Only an active supplier can be assigned to a vehicle.');
        }

        return $s;
    }

    private function validateDates(string $start, mixed $end, bool $current): void
    {
        if ($end !== null && CarbonImmutable::parse($end)->lt(CarbonImmutable::parse($start))) {
            throw new ConflictHttpException('Relationship end date cannot be before its start date.');
        }if ($current && $end !== null) {
            throw new ConflictHttpException('An ended relationship cannot be current.');
        }
    }

    private function loaded(SupplierVehicle $r): SupplierVehicle
    {
        return $r->refresh()->load(['supplier', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
    }
}

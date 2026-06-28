<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerVehicle;
use Modules\Vehicle\Models\Vehicle;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerVehicleService
{
    public function create(array $data, int $tenantId, ?int $organizationUnitId): CustomerVehicle
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId): CustomerVehicle {
            $vehicle = $this->vehicle((int) $data['vehicle_id'], $tenantId, $organizationUnitId, true);
            $customer = $this->customer((int) $data['customer_id'], $tenantId, $organizationUnitId);
            $this->lockRows((int) $vehicle->getKey());
            $this->validateDates((string) $data['started_at'], $data['ended_at'] ?? null, (bool) ($data['is_current'] ?? false));
            $active = CustomerVehicle::query()->where('vehicle_id', $vehicle->getKey())->where('customer_id', $customer->getKey())->whereNull('ended_at')->lockForUpdate()->first();
            if ($active !== null && ! (bool) ($data['is_current'] ?? false)) {
                throw new ConflictHttpException('An active Customer-Vehicle relationship already exists.');
            }
            $relationship = $active ?? new CustomerVehicle;
            $relationship->forceFill(['tenant_id' => $tenantId, 'organization_unit_id' => $organizationUnitId, 'customer_id' => $customer->getKey(), 'vehicle_id' => $vehicle->getKey(), 'relationship_type' => $data['relationship_type'] ?? 'customer_owned', 'started_at' => $active?->started_at ?? $data['started_at'], 'ended_at' => $data['ended_at'] ?? null, 'active_guard' => ($data['ended_at'] ?? null) === null ? 1 : null, 'notes' => $data['notes'] ?? $active?->notes, 'is_current' => false, 'current_guard' => null])->save();
            if ((bool) ($data['is_current'] ?? false)) {
                $this->setCurrentLocked($relationship);
            }

            return $relationship->refresh()->load(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
        }, 3);
    }

    public function update(CustomerVehicle $relationship, array $data): CustomerVehicle
    {
        return DB::transaction(function () use ($relationship, $data): CustomerVehicle {
            $this->vehicle((int) $relationship->vehicle_id, (int) $relationship->tenant_id, $relationship->organization_unit_id, true);
            $this->lockRows((int) $relationship->vehicle_id);
            $relationship = CustomerVehicle::query()->lockForUpdate()->findOrFail($relationship->getKey());
            $started = (string) ($data['started_at'] ?? $relationship->started_at);
            $ended = array_key_exists('ended_at', $data) ? $data['ended_at'] : $relationship->ended_at;
            if ($relationship->ended_at !== null && array_key_exists('ended_at', $data) && $ended === null) {
                throw new ConflictHttpException('Ended Customer-Vehicle relationships cannot be reopened. Create a new relationship instead.');
            }
            $this->validateDates($started, $ended, (bool) $relationship->is_current);
            $attributes = array_intersect_key($data, array_flip(['relationship_type', 'started_at', 'ended_at', 'notes']));
            if ($ended !== null) {
                $attributes += ['is_current' => false, 'current_guard' => null, 'active_guard' => null];
            }
            $relationship->forceFill($attributes)->save();

            return $relationship->refresh()->load(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
        }, 3);
    }

    public function setCurrent(CustomerVehicle $relationship): CustomerVehicle
    {
        return DB::transaction(function () use ($relationship): CustomerVehicle {
            $this->vehicle((int) $relationship->vehicle_id, (int) $relationship->tenant_id, $relationship->organization_unit_id, true);
            $this->lockRows((int) $relationship->vehicle_id);
            $relationship = CustomerVehicle::query()->lockForUpdate()->findOrFail($relationship->getKey());
            if ($relationship->ended_at !== null) {
                throw new ConflictHttpException('An ended Customer-Vehicle relationship cannot be current.');
            }
            $this->customer((int) $relationship->customer_id, (int) $relationship->tenant_id, $relationship->organization_unit_id);
            $this->setCurrentLocked($relationship);

            return $relationship->refresh()->load(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
        }, 3);
    }

    public function clearCurrent(CustomerVehicle $relationship): CustomerVehicle
    {
        return DB::transaction(function () use ($relationship): CustomerVehicle {
            $this->vehicle((int) $relationship->vehicle_id, (int) $relationship->tenant_id, $relationship->organization_unit_id, true);
            $this->lockRows((int) $relationship->vehicle_id);
            $relationship = CustomerVehicle::query()->lockForUpdate()->findOrFail($relationship->getKey());
            $relationship->forceFill(['is_current' => false, 'current_guard' => null])->save();

            return $relationship->refresh()->load(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
        }, 3);
    }

    public function end(CustomerVehicle $relationship, ?string $endedAt = null): CustomerVehicle
    {
        return DB::transaction(function () use ($relationship, $endedAt): CustomerVehicle {
            $this->vehicle((int) $relationship->vehicle_id, (int) $relationship->tenant_id, $relationship->organization_unit_id, true);
            $this->lockRows((int) $relationship->vehicle_id);
            $relationship = CustomerVehicle::query()->lockForUpdate()->findOrFail($relationship->getKey());
            $endedAt ??= now()->toDateTimeString();
            $this->validateDates((string) $relationship->started_at, $endedAt, false);
            $relationship->forceFill(['ended_at' => $endedAt, 'is_current' => false, 'current_guard' => null, 'active_guard' => null])->save();

            return $relationship->refresh()->load(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
        }, 3);
    }

    private function setCurrentLocked(CustomerVehicle $relationship): void
    {
        CustomerVehicle::query()->where('vehicle_id', $relationship->vehicle_id)->where('is_current', true)->whereKeyNot($relationship->getKey())->update(['is_current' => false, 'current_guard' => null, 'updated_at' => now()]);
        $relationship->forceFill(['is_current' => true, 'current_guard' => 1, 'active_guard' => 1, 'ended_at' => null])->save();
        if (CustomerVehicle::query()->where('vehicle_id', $relationship->vehicle_id)->where('is_current', true)->count() !== 1) {
            throw new \LogicException('Customer current relationship invariant failed.');
        }
    }

    private function lockRows(int $vehicleId): void
    {
        CustomerVehicle::query()->where('vehicle_id', $vehicleId)->orderBy('id')->lockForUpdate()->get();
    }

    private function vehicle(int $id, int $tenantId, ?int $organizationUnitId, bool $lock): Vehicle
    {
        $query = Vehicle::query()->whereKey($id)->where('tenant_id', $tenantId)->where(fn (Builder $q): Builder => $organizationUnitId === null ? $q->whereNull('organization_unit_id') : $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId));

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function customer(int $id, int $tenantId, ?int $organizationUnitId): Customer
    {
        $customer = Customer::query()->whereKey($id)->where('tenant_id', $tenantId)->where(fn (Builder $q): Builder => $organizationUnitId === null ? $q->whereNull('organization_unit_id') : $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId))->firstOrFail();
        if ($customer->status !== CustomerStatus::Active) {
            throw new ConflictHttpException('Only an active customer can be assigned to a vehicle.');
        }

        return $customer;
    }

    private function validateDates(string $startedAt, mixed $endedAt, bool $current): void
    {
        if ($endedAt !== null && CarbonImmutable::parse($endedAt)->lt(CarbonImmutable::parse($startedAt))) {
            throw new ConflictHttpException('Relationship end date cannot be before its start date.');
        }
        if ($current && $endedAt !== null) {
            throw new ConflictHttpException('An ended relationship cannot be current.');
        }
    }
}

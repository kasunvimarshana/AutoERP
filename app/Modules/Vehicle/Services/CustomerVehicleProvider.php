<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Contracts\CustomerVehicleProviderInterface;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Models\VehicleOwnership;

final class CustomerVehicleProvider implements CustomerVehicleProviderInterface
{
    public function findCurrentCustomerIdsByVehicleNumber(
        int $tenantId,
        ?int $organizationUnitId,
        string $search,
    ): array {
        $search = trim($search);
        if ($search === '') {
            return [];
        }

        return $this->currentCustomerVehicleQuery($tenantId, $organizationUnitId)
            ->where(function (Builder $vehicle) use ($search): void {
                $vehicle->where('vehicles.registration_number', 'like', "%{$search}%")
                    ->orWhere('vehicles.vehicle_number', 'like', "%{$search}%");
            })
            ->whereNotNull('vehicle_ownerships.owner_id')
            ->distinct()
            ->orderBy('vehicle_ownerships.owner_id')
            ->pluck('vehicle_ownerships.owner_id')
            ->map(static fn (mixed $customerId): int => (int) $customerId)
            ->values()
            ->all();
    }

    public function getCurrentVehiclesForCustomers(
        int $tenantId,
        ?int $organizationUnitId,
        array $customerIds,
    ): array {
        $customerIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $customerId): int => (int) $customerId, $customerIds),
            static fn (int $customerId): bool => $customerId > 0,
        )));

        if ($customerIds === []) {
            return [];
        }

        return $this->currentCustomerVehicleQuery($tenantId, $organizationUnitId)
            ->whereIn('vehicle_ownerships.owner_id', $customerIds)
            ->orderBy('vehicle_ownerships.owner_id')
            ->orderBy('vehicles.registration_number')
            ->get([
                'vehicle_ownerships.owner_id',
                'vehicles.id',
                'vehicles.registration_number',
            ])
            ->groupBy(static fn (VehicleOwnership $ownership): int => (int) $ownership->owner_id)
            ->mapWithKeys(static fn ($ownerships, mixed $customerId): array => [
                (int) $customerId => $ownerships->map(static fn (VehicleOwnership $ownership): array => [
                    'id' => (int) $ownership->getAttribute('id'),
                    'registration_number' => $ownership->getAttribute('registration_number') === null
                        ? null
                        : (string) $ownership->getAttribute('registration_number'),
                ])->values()->all(),
            ])
            ->all();
    }

    private function currentCustomerVehicleQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        $query = VehicleOwnership::query()
            ->join('vehicles', 'vehicles.id', '=', 'vehicle_ownerships.vehicle_id')
            ->where('vehicle_ownerships.tenant_id', $tenantId)
            ->where('vehicle_ownerships.owner_type', VehicleOwnerType::Customer->value)
            ->where('vehicle_ownerships.is_current', true)
            ->whereNull('vehicles.deleted_at');

        $this->scopeOrganization($query, $organizationUnitId);

        return $query;
    }

    private function scopeOrganization(Builder $query, ?int $organizationUnitId): void
    {
        if ($organizationUnitId === null) {
            $query->whereNull('vehicle_ownerships.organization_unit_id')
                ->whereNull('vehicles.organization_unit_id');

            return;
        }

        $query->where(function (Builder $organization) use ($organizationUnitId): void {
            $organization->whereNull('vehicle_ownerships.organization_unit_id')
                ->orWhere('vehicle_ownerships.organization_unit_id', $organizationUnitId);
        })->where(function (Builder $organization) use ($organizationUnitId): void {
            $organization->whereNull('vehicles.organization_unit_id')
                ->orWhere('vehicles.organization_unit_id', $organizationUnitId);
        });
    }
}

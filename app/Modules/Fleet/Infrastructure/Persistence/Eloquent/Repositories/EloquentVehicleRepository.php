<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Fleet\Domain\Entities\Vehicle;
use Modules\Fleet\Domain\RepositoryInterfaces\VehicleRepositoryInterface;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Models\VehicleModel;
use Modules\Fleet\Infrastructure\Persistence\Eloquent\Models\VehicleStateLogModel;

class EloquentVehicleRepository implements VehicleRepositoryInterface
{
    public function find(int $id): ?Vehicle
    {
        $model = VehicleModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByRegistration(int $tenantId, string $registration): ?Vehicle
    {
        $model = VehicleModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('registration_number', $registration)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function listByTenant(int $tenantId, array $filters = []): array
    {
        $query = VehicleModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId);

        if (isset($filters['current_state'])) {
            $query->where('current_state', $filters['current_state']);
        }
        if (isset($filters['vehicle_type_id'])) {
            $query->where('vehicle_type_id', $filters['vehicle_type_id']);
        }
        if (isset($filters['is_rentable'])) {
            $query->where('is_rentable', $filters['is_rentable']);
        }
        if (isset($filters['is_serviceable'])) {
            $query->where('is_serviceable', $filters['is_serviceable']);
        }

        return $query->get()->map(fn ($m) => $this->toEntity($m))->all();
    }

    public function listAvailableForRental(int $tenantId): array
    {
        return $this->listByTenant($tenantId, [
            'is_rentable'   => true,
            'current_state' => 'available',
        ]);
    }

    public function listAvailableForService(int $tenantId): array
    {
        return $this->listByTenant($tenantId, [
            'is_serviceable' => true,
            'current_state'  => 'available',
        ]);
    }

    public function save(Vehicle $vehicle): Vehicle
    {
        $data = [
            'tenant_id'                        => $vehicle->tenantId,
            'org_unit_id'                      => $vehicle->orgUnitId,
            'row_version'                      => 1,
            'vehicle_type_id'                  => $vehicle->vehicleTypeId,
            'registration_number'              => $vehicle->registrationNumber,
            'make'                             => $vehicle->make,
            'model'                            => $vehicle->model,
            'year'                             => $vehicle->year,
            'color'                            => $vehicle->color,
            'vin_number'                       => $vehicle->vinNumber,
            'engine_number'                    => $vehicle->engineNumber,
            'ownership_type'                   => $vehicle->ownershipType,
            'owner_supplier_id'                => $vehicle->ownerSupplierId,
            'owner_commission_pct'             => $vehicle->ownerCommissionPct,
            'is_rentable'                      => $vehicle->isRentable,
            'is_serviceable'                   => $vehicle->isServiceable,
            'current_state'                    => $vehicle->currentState,
            'current_odometer'                 => $vehicle->currentOdometer,
            'fuel_type'                        => $vehicle->fuelType,
            'fuel_capacity'                    => $vehicle->fuelCapacity,
            'seating_capacity'                 => $vehicle->seatingCapacity,
            'transmission'                     => $vehicle->transmission,
            'asset_account_id'                 => $vehicle->assetAccountId,
            'accum_depreciation_account_id'    => $vehicle->accumDepreciationAccountId,
            'depreciation_expense_account_id'  => $vehicle->depreciationExpenseAccountId,
            'rental_revenue_account_id'        => $vehicle->rentalRevenueAccountId,
            'service_revenue_account_id'       => $vehicle->serviceRevenueAccountId,
            'acquisition_cost'                 => $vehicle->acquisitionCost,
            'acquired_at'                      => $vehicle->acquiredAt,
            'disposed_at'                      => $vehicle->disposedAt,
            'metadata'                         => $vehicle->metadata ? json_encode($vehicle->metadata) : null,
            'is_active'                        => $vehicle->isActive,
        ];

        if ($vehicle->id !== null) {
            $model = VehicleModel::findOrFail($vehicle->id);
            $model->increment('row_version');
            $model->update($data);
        } else {
            $model = VehicleModel::create($data);
        }

        return $this->toEntity($model->fresh());
    }

    public function updateState(int $vehicleId, string $newState, string $updatedAt): void
    {
        VehicleModel::withoutGlobalScope('tenant')
            ->where('id', $vehicleId)
            ->update(['current_state' => $newState, 'updated_at' => $updatedAt]);
    }

    public function updateOdometer(int $vehicleId, string $odometer): void
    {
        VehicleModel::withoutGlobalScope('tenant')
            ->where('id', $vehicleId)
            ->update(['current_odometer' => $odometer]);
    }

    public function delete(int $id): void
    {
        VehicleModel::findOrFail($id)->delete();
    }

    private function toEntity(VehicleModel $model): Vehicle
    {
        return new Vehicle(
            tenantId:                       $model->tenant_id,
            vehicleTypeId:                  $model->vehicle_type_id,
            registrationNumber:             $model->registration_number,
            make:                           $model->make,
            model:                          $model->model,
            year:                           $model->year,
            ownershipType:                  $model->ownership_type,
            isRentable:                     (bool) $model->is_rentable,
            isServiceable:                  (bool) $model->is_serviceable,
            currentState:                   $model->current_state,
            currentOdometer:                (string) $model->current_odometer,
            fuelType:                       $model->fuel_type,
            transmission:                   $model->transmission,
            seatingCapacity:                $model->seating_capacity,
            color:                          $model->color,
            vinNumber:                      $model->vin_number,
            engineNumber:                   $model->engine_number,
            ownerSupplierId:                $model->owner_supplier_id,
            ownerCommissionPct:             (string) $model->owner_commission_pct,
            fuelCapacity:                   $model->fuel_capacity !== null ? (string) $model->fuel_capacity : null,
            assetAccountId:                 $model->asset_account_id,
            accumDepreciationAccountId:     $model->accum_depreciation_account_id,
            depreciationExpenseAccountId:   $model->depreciation_expense_account_id,
            rentalRevenueAccountId:         $model->rental_revenue_account_id,
            serviceRevenueAccountId:        $model->service_revenue_account_id,
            acquisitionCost:                $model->acquisition_cost !== null ? (string) $model->acquisition_cost : null,
            acquiredAt:                     $model->acquired_at,
            disposedAt:                     $model->disposed_at,
            metadata:                       $model->metadata,
            isActive:                       (bool) $model->is_active,
            orgUnitId:                      $model->org_unit_id,
            id:                             $model->id,
        );
    }
}

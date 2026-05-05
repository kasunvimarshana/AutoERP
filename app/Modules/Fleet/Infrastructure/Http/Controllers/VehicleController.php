<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Fleet\Application\Contracts\VehicleServiceInterface;
use Modules\Fleet\Application\DTOs\ChangeVehicleStateDTO;
use Modules\Fleet\Application\DTOs\CreateVehicleDTO;
use Modules\Fleet\Application\DTOs\UpdateVehicleDTO;
use Modules\Fleet\Domain\Exceptions\InvalidStateTransitionException;
use Modules\Fleet\Infrastructure\Http\Requests\ChangeVehicleStateRequest;
use Modules\Fleet\Infrastructure\Http\Requests\CreateVehicleRequest;
use Modules\Fleet\Infrastructure\Http\Requests\UpdateVehicleRequest;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicles = $this->service->listByTenant($tenantId, $request->only([
            'current_state', 'vehicle_type_id', 'is_rentable', 'is_serviceable',
        ]));

        return response()->json(['data' => array_map(fn ($v) => [
            'id'                  => $v->id,
            'registration_number' => $v->registrationNumber,
            'make'                => $v->make,
            'model'               => $v->model,
            'year'                => $v->year,
            'current_state'       => $v->currentState,
            'is_rentable'         => $v->isRentable,
            'is_serviceable'      => $v->isServiceable,
        ], $vehicles)]);
    }

    public function store(CreateVehicleRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $v = $request->validated();

        $dto = new CreateVehicleDTO(
            tenantId:                     $tenantId,
            vehicleTypeId:                $v['vehicle_type_id'],
            registrationNumber:           $v['registration_number'],
            make:                         $v['make'],
            model:                        $v['model'],
            year:                         $v['year'],
            ownershipType:                $v['ownership_type'],
            isRentable:                   (bool) $v['is_rentable'],
            isServiceable:                (bool) $v['is_serviceable'],
            fuelType:                     $v['fuel_type'],
            transmission:                 $v['transmission'],
            seatingCapacity:              $v['seating_capacity'],
            color:                        $v['color'] ?? null,
            vinNumber:                    $v['vin_number'] ?? null,
            engineNumber:                 $v['engine_number'] ?? null,
            ownerSupplierId:              $v['owner_supplier_id'] ?? null,
            ownerCommissionPct:           isset($v['owner_commission_pct']) ? (string) $v['owner_commission_pct'] : '0.00',
            fuelCapacity:                 isset($v['fuel_capacity']) ? (string) $v['fuel_capacity'] : null,
            assetAccountId:               $v['asset_account_id'] ?? null,
            accumDepreciationAccountId:   $v['accum_depreciation_account_id'] ?? null,
            depreciationExpenseAccountId: $v['depreciation_expense_account_id'] ?? null,
            rentalRevenueAccountId:       $v['rental_revenue_account_id'] ?? null,
            serviceRevenueAccountId:      $v['service_revenue_account_id'] ?? null,
            acquisitionCost:              isset($v['acquisition_cost']) ? (string) $v['acquisition_cost'] : null,
            acquiredAt:                   $v['acquired_at'] ?? null,
            orgUnitId:                    $v['org_unit_id'] ?? null,
            metadata:                     $v['metadata'] ?? null,
        );

        $vehicle = $this->service->create($dto);

        return response()->json(['data' => ['id' => $vehicle->id]], 201);
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = $this->service->find($id);

        if ($vehicle === null) {
            return response()->json(['message' => 'Vehicle not found.'], 404);
        }

        return response()->json(['data' => [
            'id'                  => $vehicle->id,
            'registration_number' => $vehicle->registrationNumber,
            'make'                => $vehicle->make,
            'model'               => $vehicle->model,
            'year'                => $vehicle->year,
            'current_state'       => $vehicle->currentState,
            'is_rentable'         => $vehicle->isRentable,
            'is_serviceable'      => $vehicle->isServiceable,
            'current_odometer'    => $vehicle->currentOdometer,
            'is_active'           => $vehicle->isActive,
        ]]);
    }

    public function update(UpdateVehicleRequest $request, int $id): JsonResponse
    {
        $v = $request->validated();

        $dto = new UpdateVehicleDTO(
            vehicleId:                    $id,
            vehicleTypeId:                $v['vehicle_type_id'] ?? null,
            color:                        $v['color'] ?? null,
            isRentable:                   isset($v['is_rentable']) ? (bool) $v['is_rentable'] : null,
            isServiceable:                isset($v['is_serviceable']) ? (bool) $v['is_serviceable'] : null,
            ownerSupplierId:              $v['owner_supplier_id'] ?? null,
            ownerCommissionPct:           isset($v['owner_commission_pct']) ? (string) $v['owner_commission_pct'] : null,
            assetAccountId:               $v['asset_account_id'] ?? null,
            accumDepreciationAccountId:   $v['accum_depreciation_account_id'] ?? null,
            depreciationExpenseAccountId: $v['depreciation_expense_account_id'] ?? null,
            rentalRevenueAccountId:       $v['rental_revenue_account_id'] ?? null,
            serviceRevenueAccountId:      $v['service_revenue_account_id'] ?? null,
            acquisitionCost:              isset($v['acquisition_cost']) ? (string) $v['acquisition_cost'] : null,
            metadata:                     $v['metadata'] ?? null,
            isActive:                     isset($v['is_active']) ? (bool) $v['is_active'] : null,
        );

        $vehicle = $this->service->update($dto);

        return response()->json(['data' => ['id' => $vehicle->id]]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }

    public function changeState(ChangeVehicleStateRequest $request, int $id): JsonResponse
    {
        $v = $request->validated();

        $dto = new ChangeVehicleStateDTO(
            vehicleId:     $id,
            toState:       $v['to_state'],
            reason:        $v['reason'],
            referenceType: $v['reference_type'] ?? null,
            referenceId:   $v['reference_id'] ?? null,
            triggeredBy:   $request->user()?->id,
        );

        try {
            $vehicle = $this->service->changeState($dto);
        } catch (InvalidStateTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => ['id' => $vehicle->id, 'current_state' => $vehicle->currentState]]);
    }

    public function availableForRental(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicles = $this->service->listAvailableForRental($tenantId);

        return response()->json(['data' => array_map(fn ($v) => [
            'id'                  => $v->id,
            'registration_number' => $v->registrationNumber,
            'make'                => $v->make,
            'model'               => $v->model,
            'year'                => $v->year,
        ], $vehicles)]);
    }

    public function availableForService(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicles = $this->service->listAvailableForService($tenantId);

        return response()->json(['data' => array_map(fn ($v) => [
            'id'                  => $v->id,
            'registration_number' => $v->registrationNumber,
            'make'                => $v->make,
            'model'               => $v->model,
        ], $vehicles)]);
    }
}

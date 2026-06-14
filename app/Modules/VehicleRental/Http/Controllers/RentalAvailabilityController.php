<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Http\Requests\VehicleAvailabilityRequest;
use Modules\VehicleRental\Services\RentalAvailabilityService;

final class RentalAvailabilityController
{
    public function index(
        VehicleAvailabilityRequest $request,
        RentalAvailabilityService $service,
    ): JsonResponse {
        $vehicleIds = $request->filled('vehicle_id')
            ? [(int) $request->input('vehicle_id')]
            : Vehicle::query()
                ->where('tenant_id', $request->tenantId())
                ->where(function (Builder $query) use ($request): void {
                    $query->whereNull('organization_unit_id');
                    if ($request->organizationUnitId() !== null) {
                        $query->orWhere('organization_unit_id', $request->organizationUnitId());
                    }
                })
                ->orderBy('vehicle_number')
                ->limit(100)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        $rows = [];
        foreach ($vehicleIds as $vehicleId) {
            $result = $service->check(
                $request->tenantId(),
                $request->organizationUnitId(),
                $vehicleId,
                (string) $request->input('start_at'),
                (string) $request->input('end_at'),
                $request->filled('exclude_agreement_id') ? (int) $request->input('exclude_agreement_id') : null,
                $request->filled('exclude_reservation_id') ? (int) $request->input('exclude_reservation_id') : null,
            );
            $vehicle = $result['vehicle']->loadMissing(['make', 'model']);
            $rows[] = [
                'vehicle' => [
                    'id' => (int) $vehicle->getKey(),
                    'code' => $vehicle->vehicle_number,
                    'name' => $vehicle->registration_number ?? $vehicle->vehicle_number,
                    'registration_number' => $vehicle->registration_number,
                    'make' => $vehicle->make?->name,
                    'model' => $vehicle->model?->name,
                ],
                'available' => $result['available'],
                'reasons' => $result['reasons'],
            ];
        }

        return response()->json(['data' => $rows]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Infrastructure\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FuelTracking\Application\Contracts\FuelLogServiceInterface;
use Modules\FuelTracking\Application\DTOs\CreateFuelLogDTO;
use Modules\FuelTracking\Domain\Exceptions\FuelLogNotFoundException;
use Modules\FuelTracking\Domain\ValueObjects\FuelType;
use Modules\FuelTracking\Infrastructure\Http\Requests\CreateFuelLogRequest;

class FuelLogController extends Controller
{
    public function __construct(
        private readonly FuelLogServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = (string) $request->header('X-Tenant-ID', '');
        $orgUnitId = (string) $request->query('org_unit_id', $tenantId);

        $logs = $this->service->getByTenant($tenantId, $orgUnitId);

        return response()->json(['data' => array_map([$this, 'serialize'], $logs)]);
    }

    public function store(CreateFuelLogRequest $request): JsonResponse
    {
        $tenantId  = (string) $request->header('X-Tenant-ID', '');
        $orgUnitId = (string) $request->input('org_unit_id', $tenantId);
        $validated = $request->validated();

        $dto = new CreateFuelLogDTO(
            tenantId: $tenantId,
            orgUnitId: $orgUnitId,
            logNumber: $validated['log_number'],
            vehicleId: $validated['vehicle_id'],
            driverId: $validated['driver_id'] ?? null,
            fuelType: FuelType::from($validated['fuel_type']),
            odoReading: (string) $validated['odometer_reading'],
            litres: (string) $validated['litres'],
            costPerLitre: (string) $validated['cost_per_litre'],
            totalCost: (string) $validated['total_cost'],
            stationName: $validated['station_name'] ?? null,
            filledAt: isset($validated['filled_at'])
                ? new DateTimeImmutable($validated['filled_at'])
                : null,
            notes: $validated['notes'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        $log = $this->service->createLog($dto);

        return response()->json(['data' => $this->serialize($log)], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $log = $this->service->getLog($id);
            return response()->json(['data' => $this->serialize($log)]);
        } catch (FuelLogNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->getLog($id);
            $this->service->deleteLog($id);
            return response()->json(null, 204);
        } catch (FuelLogNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function byVehicle(Request $request, string $vehicleId): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID', '');
        $logs     = $this->service->getByVehicle($tenantId, $vehicleId);

        return response()->json(['data' => array_map([$this, 'serialize'], $logs)]);
    }

    public function byDriver(Request $request, string $driverId): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID', '');
        $logs     = $this->service->getByDriver($tenantId, $driverId);

        return response()->json(['data' => array_map([$this, 'serialize'], $logs)]);
    }

    private function serialize(\Modules\FuelTracking\Domain\Entities\FuelLog $log): array
    {
        return [
            'id'               => $log->id,
            'tenant_id'        => $log->tenantId,
            'org_unit_id'      => $log->orgUnitId,
            'row_version'      => $log->rowVersion,
            'log_number'       => $log->logNumber,
            'vehicle_id'       => $log->vehicleId,
            'driver_id'        => $log->driverId,
            'fuel_type'        => $log->fuelType->value,
            'odometer_reading' => $log->odoReading,
            'litres'           => $log->litres,
            'cost_per_litre'   => $log->costPerLitre,
            'total_cost'       => $log->totalCost,
            'station_name'     => $log->stationName,
            'filled_at'        => $log->filledAt?->format('Y-m-d H:i:s'),
            'notes'            => $log->notes,
            'metadata'         => $log->metadata,
            'is_active'        => $log->isActive,
        ];
    }
}

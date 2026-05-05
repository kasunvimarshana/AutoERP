<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Fleet\Application\Contracts\VehicleTypeServiceInterface;
use Modules\Fleet\Application\DTOs\CreateVehicleTypeDTO;
use Modules\Fleet\Infrastructure\Http\Requests\CreateVehicleTypeRequest;
use Modules\Fleet\Infrastructure\Http\Resources\VehicleTypeResource;

class VehicleTypeController extends Controller
{
    public function __construct(
        private readonly VehicleTypeServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $types = $this->service->listByTenant($tenantId);

        return response()->json(['data' => array_map(
            fn ($t) => new VehicleTypeResource((object) [
                'id'               => $t->id,
                'tenant_id'        => $t->tenantId,
                'name'             => $t->name,
                'description'      => $t->description,
                'base_daily_rate'  => $t->baseDailyRate,
                'base_hourly_rate' => $t->baseHourlyRate,
                'seating_capacity' => $t->seatingCapacity,
                'is_active'        => $t->isActive,
                'created_at'       => null,
                'updated_at'       => null,
            ]),
            $types
        )]);
    }

    public function store(CreateVehicleTypeRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $validated = $request->validated();

        $dto = new CreateVehicleTypeDTO(
            tenantId:        $tenantId,
            name:            $validated['name'],
            description:     $validated['description'] ?? null,
            baseDailyRate:   isset($validated['base_daily_rate']) ? (string) $validated['base_daily_rate'] : '0.000000',
            baseHourlyRate:  isset($validated['base_hourly_rate']) ? (string) $validated['base_hourly_rate'] : '0.000000',
            seatingCapacity: $validated['seating_capacity'] ?? 1,
            isActive:        $validated['is_active'] ?? true,
            orgUnitId:       $validated['org_unit_id'] ?? null,
        );

        $type = $this->service->create($dto);

        return response()->json(['data' => ['id' => $type->id]], 201);
    }

    public function show(int $id): JsonResponse
    {
        $type = $this->service->find($id);

        if ($type === null) {
            return response()->json(['message' => 'Vehicle type not found.'], 404);
        }

        return response()->json(['data' => ['id' => $type->id, 'name' => $type->name]]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'             => ['sometimes', 'string', 'max:100'],
            'description'      => ['sometimes', 'nullable', 'string'],
            'base_daily_rate'  => ['sometimes', 'numeric', 'min:0'],
            'base_hourly_rate' => ['sometimes', 'numeric', 'min:0'],
            'seating_capacity' => ['sometimes', 'integer', 'min:1'],
            'is_active'        => ['sometimes', 'boolean'],
        ]);

        $type = $this->service->update($id, $validated);

        return response()->json(['data' => ['id' => $type->id]]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(null, 204);
    }
}

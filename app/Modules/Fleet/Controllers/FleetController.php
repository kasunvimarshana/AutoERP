<?php

namespace App\Modules\Fleet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fleet\Services\MaintenanceService;
use App\Modules\Fleet\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fleet Controller
 *
 * @OA\Tag(name="Fleet", description="Fleet management endpoints")
 */
class FleetController extends Controller
{
    protected VehicleService $vehicleService;

    protected MaintenanceService $maintenanceService;

    public function __construct(
        VehicleService $vehicleService,
        MaintenanceService $maintenanceService
    ) {
        $this->vehicleService = $vehicleService;
        $this->maintenanceService = $maintenanceService;
    }

    public function vehicles(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $vehicles = $this->vehicleService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $vehicles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|max:50|unique:vehicles',
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'type' => 'nullable|string|max:50',
            'capacity' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,maintenance,inactive,retired',
            'acquisition_date' => 'nullable|date',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $vehicle = $this->vehicleService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle added successfully',
                'data' => $vehicle,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add vehicle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showVehicle(int $id): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->find($id);

            if (! $vehicle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $vehicle,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateVehicle(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'registration_number' => 'sometimes|string|max:50|unique:vehicles,registration_number,'.$id,
            'make' => 'sometimes|string|max:100',
            'model' => 'sometimes|string|max:100',
            'year' => 'sometimes|integer|min:1900|max:'.(date('Y') + 1),
            'type' => 'nullable|string|max:50',
            'capacity' => 'nullable|string|max:50',
            'mileage' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:active,maintenance,inactive,retired',
            'acquisition_date' => 'nullable|date',
        ]);

        try {
            $result = $this->vehicleService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyVehicle(int $id): JsonResponse
    {
        try {
            $result = $this->vehicleService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vehicle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function maintenance(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $maintenanceRecords = $this->maintenanceService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $maintenanceRecords,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeMaintenance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'status' => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
            'mileage_at_maintenance' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $maintenance = $this->maintenanceService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance record created successfully',
                'data' => $maintenance,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create maintenance record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showMaintenance(int $id): JsonResponse
    {
        try {
            $maintenance = $this->maintenanceService->find($id);

            if (! $maintenance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maintenance record not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $maintenance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateMaintenance(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'type' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'cost' => 'sometimes|numeric|min:0',
            'scheduled_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'status' => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
            'mileage_at_maintenance' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->maintenanceService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance record updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyMaintenance(int $id): JsonResponse
    {
        try {
            $result = $this->maintenanceService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance record deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete maintenance record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Modules\Vehicle\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Vehicle\Services\VehicleService;
use App\Modules\Vehicle\DTOs\VehicleDTO;
use App\Modules\Vehicle\Http\Requests\StoreVehicleRequest;
use App\Modules\Vehicle\Http\Requests\UpdateVehicleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Vehicles",
 *     description="Vehicle management endpoints"
 * )
 */
class VehicleController extends BaseController
{
    protected VehicleService $service;

    public function __construct(VehicleService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vehicles",
     *     tags={"Vehicles"},
     *     summary="List all vehicles",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicles retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $vehicles = $this->service->paginate($perPage, ['tenant', 'customer', 'branch']);
            
            return $this->successResponse($vehicles, 'Vehicles retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vehicles",
     *     tags={"Vehicles"},
     *     summary="Create a new vehicle",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "customer_id", "branch_id", "registration_number", "make", "model"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="string", format="uuid"),
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="vin", type="string", example="1HGBH41JXMN109186"),
     *             @OA\Property(property="registration_number", type="string", example="ABC-1234"),
     *             @OA\Property(property="make", type="string", example="Toyota"),
     *             @OA\Property(property="model", type="string", example="Camry"),
     *             @OA\Property(property="year", type="integer", example=2024),
     *             @OA\Property(property="color", type="string", example="Blue"),
     *             @OA\Property(property="fuel_type", type="string", example="Petrol"),
     *             @OA\Property(property="transmission_type", type="string", example="Automatic"),
     *             @OA\Property(property="engine_number", type="string"),
     *             @OA\Property(property="chassis_number", type="string"),
     *             @OA\Property(property="mileage", type="integer", example=50000),
     *             @OA\Property(property="purchase_date", type="string", format="date"),
     *             @OA\Property(property="last_service_date", type="string", format="date"),
     *             @OA\Property(property="next_service_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            $dto = VehicleDTO::fromArray($request->validated());
            $vehicle = $this->service->createVehicle($dto);
            
            return $this->successResponse($vehicle, 'Vehicle created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vehicles/{id}",
     *     tags={"Vehicles"},
     *     summary="Get a specific vehicle",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $vehicle = $this->service->findById($id, ['tenant', 'customer', 'branch']);
            
            if (!$vehicle) {
                return $this->errorResponse('Vehicle not found', 404);
            }
            
            return $this->successResponse($vehicle, 'Vehicle retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/vehicles/{id}",
     *     tags={"Vehicles"},
     *     summary="Update a vehicle",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="vin", type="string"),
     *             @OA\Property(property="registration_number", type="string"),
     *             @OA\Property(property="make", type="string"),
     *             @OA\Property(property="model", type="string"),
     *             @OA\Property(property="year", type="integer"),
     *             @OA\Property(property="color", type="string"),
     *             @OA\Property(property="fuel_type", type="string"),
     *             @OA\Property(property="transmission_type", type="string"),
     *             @OA\Property(property="engine_number", type="string"),
     *             @OA\Property(property="chassis_number", type="string"),
     *             @OA\Property(property="mileage", type="integer"),
     *             @OA\Property(property="purchase_date", type="string", format="date"),
     *             @OA\Property(property="last_service_date", type="string", format="date"),
     *             @OA\Property(property="next_service_date", type="string", format="date"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle not found"
     *     )
     * )
     */
    public function update(UpdateVehicleRequest $request, string $id): JsonResponse
    {
        try {
            $dto = VehicleDTO::fromArray($request->validated());
            $result = $this->service->updateVehicle($id, $dto);
            
            if (!$result) {
                return $this->errorResponse('Vehicle not found or update failed', 404);
            }
            
            $vehicle = $this->service->findById($id, ['tenant', 'customer', 'branch']);
            
            return $this->successResponse($vehicle, 'Vehicle updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/vehicles/{id}",
     *     tags={"Vehicles"},
     *     summary="Delete a vehicle",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->deleteVehicle($id);
            
            if (!$result) {
                return $this->errorResponse('Vehicle not found or delete failed', 404);
            }
            
            return $this->successResponse(null, 'Vehicle deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/vehicles/{id}/restore",
     *     tags={"Vehicles"},
     *     summary="Restore a soft-deleted vehicle",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle restored successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle not found"
     *     )
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $result = $this->service->restore($id);
            
            if (!$result) {
                return $this->errorResponse('Vehicle not found or restore failed', 404);
            }
            
            return $this->successResponse(null, 'Vehicle restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vehicles/{id}/history",
     *     tags={"Vehicles"},
     *     summary="Get vehicle service history",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Vehicle ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle history retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="vehicle", ref="#/components/schemas/Vehicle"),
     *                 @OA\Property(property="service_history", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="branch", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vehicle not found"
     *     )
     * )
     */
    public function history(string $id): JsonResponse
    {
        try {
            $history = $this->service->getVehicleHistory($id);
            
            if (empty($history)) {
                return $this->errorResponse('Vehicle not found', 404);
            }
            
            return $this->successResponse($history, 'Vehicle history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

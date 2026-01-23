<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Requests\StoreVehicleRequest;
use Modules\Customer\Requests\UpdateVehicleRequest;
use Modules\Customer\Resources\VehicleResource;
use Modules\Customer\Services\VehicleService;
use OpenApi\Attributes as OA;

/**
 * Vehicle Controller
 *
 * Handles HTTP requests for Vehicle operations
 * Follows Controller → Service → Repository pattern
 */
class VehicleController extends Controller
{
    /**
     * VehicleController constructor
     */
    public function __construct(
        private readonly VehicleService $vehicleService
    ) {}

    /**
     * Display a listing of vehicles
     *
     * @OA\Get(
     *     path="/api/v1/vehicles",
     *     summary="List all vehicles",
     *     description="Get a paginated list of all vehicles with optional filtering",
     *     operationId="getVehicles",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="paginate",
     *         in="query",
     *         description="Enable pagination",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicles retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicles retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Vehicle")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $vehicles = $this->vehicleService->getAll($filters);

        return $this->successResponse(
            VehicleResource::collection($vehicles),
            __('customer::messages.vehicles_retrieved')
        );
    }

    /**
     * Store a newly created vehicle
     *
     * @OA\Post(
     *     path="/api/v1/vehicles",
     *     summary="Create a new vehicle",
     *     description="Create a new vehicle record",
     *     operationId="createVehicle",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"customer_id", "registration_number", "make", "model", "year"},
     *
     *             @OA\Property(property="customer_id", type="integer", example=1, description="Owner customer ID"),
     *             @OA\Property(property="registration_number", type="string", example="ABC-123", description="Vehicle registration number"),
     *             @OA\Property(property="vin", type="string", example="1HGBH41JXMN109186", description="Vehicle Identification Number"),
     *             @OA\Property(property="make", type="string", example="Toyota", description="Vehicle make"),
     *             @OA\Property(property="model", type="string", example="Camry", description="Vehicle model"),
     *             @OA\Property(property="year", type="integer", example=2023, description="Manufacturing year"),
     *             @OA\Property(property="color", type="string", example="Silver", description="Vehicle color"),
     *             @OA\Property(property="engine_number", type="string", example="ENG123456", description="Engine number"),
     *             @OA\Property(property="chassis_number", type="string", example="CHS123456", description="Chassis number"),
     *             @OA\Property(property="fuel_type", type="string", example="Petrol", description="Fuel type"),
     *             @OA\Property(property="transmission", type="string", example="Automatic", description="Transmission type"),
     *             @OA\Property(property="current_mileage", type="integer", example=15000, description="Current mileage"),
     *             @OA\Property(property="purchase_date", type="string", format="date", example="2023-01-15", description="Purchase date"),
     *             @OA\Property(property="registration_date", type="string", format="date", example="2023-01-20", description="Registration date"),
     *             @OA\Property(property="insurance_expiry", type="string", format="date", example="2024-12-31", description="Insurance expiry date"),
     *             @OA\Property(property="insurance_provider", type="string", example="ABC Insurance", description="Insurance provider"),
     *             @OA\Property(property="insurance_policy_number", type="string", example="POL123456", description="Insurance policy number"),
     *             @OA\Property(property="next_service_mileage", type="integer", example=20000, description="Next service mileage"),
     *             @OA\Property(property="next_service_date", type="string", format="date", example="2024-06-10", description="Next service date"),
     *             @OA\Property(property="notes", type="string", example="Requires premium oil", description="Additional notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Vehicle created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->vehicleService->create($request->validated());

        return $this->createdResponse(
            new VehicleResource($vehicle),
            __('customer::messages.vehicle_created')
        );
    }

    /**
     * Display the specified vehicle
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{id}",
     *     summary="Get vehicle by ID",
     *     description="Retrieve a specific vehicle's details by ID",
     *     operationId="getVehicleById",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = $this->vehicleService->getById($id);

        return $this->successResponse(
            new VehicleResource($vehicle),
            __('customer::messages.vehicle_retrieved')
        );
    }

    /**
     * Update the specified vehicle
     *
     * @OA\Put(
     *     path="/api/v1/vehicles/{id}",
     *     summary="Update vehicle",
     *     description="Update an existing vehicle's information",
     *     operationId="updateVehicle",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="registration_number", type="string", example="ABC-123", description="Vehicle registration number"),
     *             @OA\Property(property="vin", type="string", example="1HGBH41JXMN109186", description="Vehicle Identification Number"),
     *             @OA\Property(property="make", type="string", example="Toyota", description="Vehicle make"),
     *             @OA\Property(property="model", type="string", example="Camry", description="Vehicle model"),
     *             @OA\Property(property="year", type="integer", example=2023, description="Manufacturing year"),
     *             @OA\Property(property="color", type="string", example="Silver", description="Vehicle color"),
     *             @OA\Property(property="engine_number", type="string", example="ENG123456", description="Engine number"),
     *             @OA\Property(property="chassis_number", type="string", example="CHS123456", description="Chassis number"),
     *             @OA\Property(property="fuel_type", type="string", example="Petrol", description="Fuel type"),
     *             @OA\Property(property="transmission", type="string", example="Automatic", description="Transmission type"),
     *             @OA\Property(property="current_mileage", type="integer", example=15000, description="Current mileage"),
     *             @OA\Property(property="insurance_expiry", type="string", format="date", example="2024-12-31", description="Insurance expiry date"),
     *             @OA\Property(property="insurance_provider", type="string", example="ABC Insurance", description="Insurance provider"),
     *             @OA\Property(property="insurance_policy_number", type="string", example="POL123456", description="Insurance policy number"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "sold"}, example="active", description="Vehicle status"),
     *             @OA\Property(property="next_service_mileage", type="integer", example=20000, description="Next service mileage"),
     *             @OA\Property(property="next_service_date", type="string", format="date", example="2024-06-10", description="Next service date"),
     *             @OA\Property(property="notes", type="string", example="Requires premium oil", description="Additional notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(UpdateVehicleRequest $request, int $id): JsonResponse
    {
        $vehicle = $this->vehicleService->update($id, $request->validated());

        return $this->successResponse(
            new VehicleResource($vehicle),
            __('customer::messages.vehicle_updated')
        );
    }

    /**
     * Remove the specified vehicle
     *
     * @OA\Delete(
     *     path="/api/v1/vehicles/{id}",
     *     summary="Delete vehicle",
     *     description="Delete a vehicle from the system",
     *     operationId="deleteVehicle",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->vehicleService->delete($id);

        return $this->successResponse(
            null,
            __('customer::messages.vehicle_deleted')
        );
    }

    /**
     * Get vehicle with all relations
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{id}/with-relations",
     *     summary="Get vehicle with relations",
     *     description="Retrieve a vehicle with all related data (customer, service records)",
     *     operationId="getVehicleWithRelations",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicle with relations retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicle retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function withRelations(int $id): JsonResponse
    {
        $vehicle = $this->vehicleService->getWithRelations($id);

        return $this->successResponse(
            new VehicleResource($vehicle),
            __('customer::messages.vehicle_retrieved')
        );
    }

    /**
     * Get vehicles by customer
     *
     * @OA\Get(
     *     path="/api/v1/customers/{customerId}/vehicles",
     *     summary="Get vehicles by customer",
     *     description="Retrieve all vehicles belonging to a specific customer",
     *     operationId="getVehiclesByCustomer",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="customerId",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicles retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicles retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Vehicle")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function byCustomer(int $customerId): JsonResponse
    {
        $vehicles = $this->vehicleService->getByCustomer($customerId);

        return $this->successResponse(
            VehicleResource::collection($vehicles),
            __('customer::messages.vehicles_retrieved')
        );
    }

    /**
     * Search vehicles
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/search",
     *     summary="Search vehicles",
     *     description="Search for vehicles by registration number, VIN, make, model, or customer name",
     *     operationId="searchVehicles",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query (minimum 2 characters)",
     *
     *         @OA\Schema(type="string", example="Toyota", minLength=2)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicles retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Vehicle")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
        ]);

        $vehicles = $this->vehicleService->search($request->input('query'));

        return $this->successResponse(
            VehicleResource::collection($vehicles),
            __('customer::messages.vehicles_retrieved')
        );
    }

    /**
     * Get vehicles due for service
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/due-for-service",
     *     summary="Get vehicles due for service",
     *     description="Retrieve all vehicles that are due for service based on mileage or date",
     *     operationId="getVehiclesDueForService",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicles due for service retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicles due for service retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Vehicle")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function dueForService(): JsonResponse
    {
        $vehicles = $this->vehicleService->getDueForService();

        return $this->successResponse(
            VehicleResource::collection($vehicles),
            __('customer::messages.vehicles_due_for_service')
        );
    }

    /**
     * Get vehicles with expiring insurance
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/expiring-insurance",
     *     summary="Get vehicles with expiring insurance",
     *     description="Retrieve vehicles with insurance expiring within specified days threshold",
     *     operationId="getVehiclesWithExpiringInsurance",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Days threshold (default: 30)",
     *
     *         @OA\Schema(type="integer", example=30, default=30)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vehicles with expiring insurance retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Vehicles with expiring insurance retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Vehicle")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function expiringInsurance(Request $request): JsonResponse
    {
        $daysThreshold = $request->integer('days', 30);
        $vehicles = $this->vehicleService->getWithExpiringInsurance($daysThreshold);

        return $this->successResponse(
            VehicleResource::collection($vehicles),
            __('customer::messages.vehicles_expiring_insurance')
        );
    }

    /**
     * Update vehicle mileage
     *
     * @OA\Patch(
     *     path="/api/v1/vehicles/{id}/mileage",
     *     summary="Update vehicle mileage",
     *     description="Update the current mileage of a vehicle",
     *     operationId="updateVehicleMileage",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"mileage"},
     *
     *             @OA\Property(property="mileage", type="integer", example=18000, description="New mileage value", minimum=0)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Mileage updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mileage updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function updateMileage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'mileage' => ['required', 'integer', 'min:0'],
        ]);

        $vehicle = $this->vehicleService->updateMileage($id, $request->integer('mileage'));

        return $this->successResponse(
            new VehicleResource($vehicle),
            __('customer::messages.mileage_updated')
        );
    }

    /**
     * Transfer vehicle ownership
     *
     * @OA\Post(
     *     path="/api/v1/vehicles/{id}/transfer-ownership",
     *     summary="Transfer vehicle ownership",
     *     description="Transfer a vehicle to a new customer",
     *     operationId="transferVehicleOwnership",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"new_customer_id"},
     *
     *             @OA\Property(property="new_customer_id", type="integer", example=2, description="New owner customer ID"),
     *             @OA\Property(property="notes", type="string", example="Vehicle transferred due to sale", description="Transfer notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ownership transferred successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ownership transferred successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Vehicle")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle or customer not found", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function transferOwnership(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'new_customer_id' => ['required', 'integer', 'exists:customers,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $vehicle = $this->vehicleService->transferOwnership(
            $id,
            $request->integer('new_customer_id'),
            $request->input('notes', '')
        );

        return $this->successResponse(
            new VehicleResource($vehicle),
            __('customer::messages.ownership_transferred')
        );
    }

    /**
     * Get vehicle service statistics
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{id}/statistics",
     *     summary="Get vehicle service statistics",
     *     description="Retrieve comprehensive service statistics for a vehicle",
     *     operationId="getVehicleServiceStatistics",
     *     tags={"Vehicles"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *
     *                 @OA\Property(property="total_services", type="integer", example=5),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=625.00),
     *                 @OA\Property(property="last_service_date", type="string", format="date-time", example="2024-01-10T10:00:00Z"),
     *                 @OA\Property(property="next_service_due", type="string", format="date", example="2024-06-10"),
     *                 @OA\Property(property="average_service_cost", type="number", format="float", example=125.00)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function serviceStatistics(int $id): JsonResponse
    {
        $statistics = $this->vehicleService->getServiceStatistics($id);

        return $this->successResponse(
            $statistics,
            __('customer::messages.statistics_retrieved')
        );
    }
}

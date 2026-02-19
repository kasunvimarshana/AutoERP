<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Requests\StoreVehicleServiceRecordRequest;
use Modules\Customer\Requests\UpdateVehicleServiceRecordRequest;
use Modules\Customer\Resources\VehicleServiceRecordResource;
use Modules\Customer\Services\VehicleServiceRecordService;
use OpenApi\Attributes as OA;

/**
 * Vehicle Service Record Controller
 *
 * Handles HTTP requests for service record operations
 * Supports cross-branch service history tracking
 */
class VehicleServiceRecordController extends Controller
{
    /**
     * VehicleServiceRecordController constructor
     */

    public function __construct(
        private readonly VehicleServiceRecordService $serviceRecordService
    ) {}

    /**
     * Display a listing of service records
     *
     * @OA\Get(
     *     path="/api/v1/service-records",
     *     summary="List all service records",
     *     description="Get a paginated list of all service records",
     *     operationId="getServiceRecords",
     *     tags={"Service Records"},
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
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
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

        $serviceRecords = $this->serviceRecordService->getAll($filters);

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Store a newly created service record
     *
     * @OA\Post(
     *     path="/api/v1/service-records",
     *     summary="Create a new service record",
     *     description="Create a new vehicle service record",
     *     operationId="createServiceRecord",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"vehicle_id", "service_date", "service_type"},
     *
     *             @OA\Property(property="vehicle_id", type="integer", example=1, description="Vehicle ID"),
     *             @OA\Property(property="customer_id", type="integer", example=1, description="Customer ID"),
     *             @OA\Property(property="branch_id", type="string", example="BRANCH-01", description="Service branch ID"),
     *             @OA\Property(property="service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Service date and time"),
     *             @OA\Property(property="mileage_at_service", type="integer", example=15000, description="Vehicle mileage at service"),
     *             @OA\Property(property="service_type", type="string", example="Oil Change", description="Type of service"),
     *             @OA\Property(property="service_description", type="string", example="Full synthetic oil change with filter replacement", description="Detailed service description"),
     *             @OA\Property(property="parts_used", type="string", example="Oil filter, Engine oil 5W-30", description="Parts used during service"),
     *             @OA\Property(property="labor_cost", type="number", format="float", example=50 . 00, description="Labor cost"),
     *             @OA\Property(property="parts_cost", type="number", format="float", example=75 . 00, description="Parts cost"),
     *             @OA\Property(property="total_cost", type="number", format="float", example=125 . 00, description="Total service cost"),
     *             @OA\Property(property="technician_name", type="string", example="John Smith", description="Technician name"),
     *             @OA\Property(property="technician_id", type="integer", example=1, description="Technician ID"),
     *             @OA\Property(property="next_service_mileage", type="integer", example=20000, description="Recommended next service mileage"),
     *             @OA\Property(property="next_service_date", type="string", format="date", example="2024-06-15", description="Recommended next service date"),
     *             @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "cancelled"}, example="pending", description="Service status"),
     *             @OA\Property(property="notes", type="string", example="Customer approved additional service", description="Additional notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Service record created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service record created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleServiceRecord")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function store(StoreVehicleServiceRecordRequest $request): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->create($request->validated());

        return $this->createdResponse(
            new VehicleServiceRecordResource($serviceRecord),
            __('customer::messages . service_record_created')
        );
    }

    /**
     * Display the specified service record
     *
     * @OA\Get(
     *     path="/api/v1/service-records/{id}",
     *     summary="Get service record by ID",
     *     description="Retrieve a specific service record by ID",
     *     operationId="getServiceRecordById",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service record ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service record retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service record retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleServiceRecord")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Service record not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function show(int $id): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->getById($id);

        return $this->successResponse(
            new VehicleServiceRecordResource($serviceRecord),
            __('customer::messages . service_record_retrieved')
        );
    }

    /**
     * Update the specified service record
     *
     * @OA\Put(
     *     path="/api/v1/service-records/{id}",
     *     summary="Update service record",
     *     description="Update an existing service record",
     *     operationId="updateServiceRecord",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service record ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Service date and time"),
     *             @OA\Property(property="mileage_at_service", type="integer", example=15000, description="Vehicle mileage at service"),
     *             @OA\Property(property="service_type", type="string", example="Oil Change", description="Type of service"),
     *             @OA\Property(property="service_description", type="string", example="Full synthetic oil change", description="Service description"),
     *             @OA\Property(property="parts_used", type="string", example="Oil filter, Engine oil", description="Parts used"),
     *             @OA\Property(property="labor_cost", type="number", format="float", example=50 . 00, description="Labor cost"),
     *             @OA\Property(property="parts_cost", type="number", format="float", example=75 . 00, description="Parts cost"),
     *             @OA\Property(property="total_cost", type="number", format="float", example=125 . 00, description="Total cost"),
     *             @OA\Property(property="technician_name", type="string", example="John Smith", description="Technician name"),
     *             @OA\Property(property="next_service_mileage", type="integer", example=20000, description="Next service mileage"),
     *             @OA\Property(property="next_service_date", type="string", format="date", example="2024-06-15", description="Next service date"),
     *             @OA\Property(property="status", type="string", enum={"pending", "in_progress", "completed", "cancelled"}, example="in_progress", description="Service status"),
     *             @OA\Property(property="notes", type="string", example="Additional notes", description="Notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service record updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service record updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleServiceRecord")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Service record not found", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function update(UpdateVehicleServiceRecordRequest $request, int $id): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->update($id, $request->validated());

        return $this->successResponse(
            new VehicleServiceRecordResource($serviceRecord),
            __('customer::messages . service_record_updated')
        );
    }

    /**
     * Remove the specified service record
     *
     * @OA\Delete(
     *     path="/api/v1/service-records/{id}",
     *     summary="Delete service record",
     *     description="Delete a service record from the system",
     *     operationId="deleteServiceRecord",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service record ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service record deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service record deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Service record not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function destroy(int $id): JsonResponse
    {
        $this->serviceRecordService->delete($id);

        return $this->successResponse(
            null,
            __('customer::messages . service_record_deleted')
        );
    }

    /**
     * Get service record with relations
     *
     * @OA\Get(
     *     path="/api/v1/service-records/{id}/with-relations",
     *     summary="Get service record with relations",
     *     description="Retrieve a service record with all related data (vehicle, customer)",
     *     operationId="getServiceRecordWithRelations",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service record ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service record with relations retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service record retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleServiceRecord")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Service record not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function withRelations(int $id): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->getWithRelations($id);

        return $this->successResponse(
            new VehicleServiceRecordResource($serviceRecord),
            __('customer::messages . service_record_retrieved')
        );
    }

    /**
     * Get service records for a vehicle
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{vehicleId}/service-records",
     *     summary="Get service records by vehicle",
     *     description="Retrieve all service records for a specific vehicle",
     *     operationId="getServiceRecordsByVehicle",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="vehicleId",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function byVehicle(int $vehicleId): JsonResponse
    {
        $serviceRecords = $this->serviceRecordService->getByVehicle($vehicleId);

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get service records for a customer
     *
     * @OA\Get(
     *     path="/api/v1/customers/{customerId}/service-records",
     *     summary="Get service records by customer",
     *     description="Retrieve all service records for all vehicles owned by a customer",
     *     operationId="getServiceRecordsByCustomer",
     *     tags={"Service Records"},
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
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
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
        $serviceRecords = $this->serviceRecordService->getByCustomer($customerId);

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get service records by branch
     *
     * @OA\Get(
     *     path="/api/v1/service-records/by-branch",
     *     summary="Get service records by branch",
     *     description="Retrieve service records filtered by service branch",
     *     operationId="getServiceRecordsByBranch",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=true,
     *         description="Branch ID",
     *
     *         @OA\Schema(type="string", example="BRANCH-01")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function byBranch(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => ['required', 'string'],
        ]);

        $serviceRecords = $this->serviceRecordService->getByBranch($request->input('branch_id'));

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get cross-branch service history for a vehicle
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{vehicleId}/cross-branch-history",
     *     summary="Get cross-branch service history",
     *     description="Retrieve service history for a vehicle across all branches",
     *     operationId="getCrossBranchServiceHistory",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="vehicleId",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cross-branch service history retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service history retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function crossBranchHistory(int $vehicleId): JsonResponse
    {
        $serviceRecords = $this->serviceRecordService->getCrossBranchHistory($vehicleId);

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_history_retrieved')
        );
    }

    /**
     * Get service records by service type
     *
     * @OA\Get(
     *     path="/api/v1/service-records/by-service-type",
     *     summary="Get service records by service type",
     *     description="Retrieve service records filtered by service type (e . g., Oil Change, Brake Service)",
     *     operationId="getServiceRecordsByServiceType",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="service_type",
     *         in="query",
     *         required=true,
     *         description="Service type",
     *
     *         @OA\Schema(type="string", example="Oil Change")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function byServiceType(Request $request): JsonResponse
    {
        $request->validate([
            'service_type' => ['required', 'string'],
        ]);

        $serviceRecords = $this->serviceRecordService->getByServiceType($request->input('service_type'));

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get service records by status
     *
     * @OA\Get(
     *     path="/api/v1/service-records/by-status",
     *     summary="Get service records by status",
     *     description="Retrieve service records filtered by status",
     *     operationId="getServiceRecordsByStatus",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=true,
     *         description="Service status",
     *
     *         @OA\Schema(type="string", enum={"pending", "in_progress", "completed", "cancelled"}, example="completed")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function byStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        $serviceRecords = $this->serviceRecordService->getByStatus($request->input('status'));

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get pending service records
     *
     * @OA\Get(
     *     path="/api/v1/service-records/pending",
     *     summary="Get pending service records",
     *     description="Retrieve all service records with pending status",
     *     operationId="getPendingServiceRecords",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pending service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function pending(): JsonResponse
    {
        $serviceRecords = $this->serviceRecordService->getPending();

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get in-progress service records
     *
     * @OA\Get(
     *     path="/api/v1/service-records/in-progress",
     *     summary="Get in-progress service records",
     *     description="Retrieve all service records with in-progress status",
     *     operationId="getInProgressServiceRecords",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="In-progress service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function inProgress(): JsonResponse
    {
        $serviceRecords = $this->serviceRecordService->getInProgress();

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Complete a service record
     *
     * @OA\Post(
     *     path="/api/v1/service-records/{id}/complete",
     *     summary="Complete a service record",
     *     description="Mark a service record as completed",
     *     operationId="completeServiceRecord",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service record ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service completed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service completed successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleServiceRecord")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Service record not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function complete(int $id): JsonResponse
    {
        $serviceRecord = $this->serviceRecordService->complete($id);

        return $this->successResponse(
            new VehicleServiceRecordResource($serviceRecord),
            __('customer::messages . service_completed')
        );
    }

    /**
     * Cancel a service record
     *
     * @OA\Post(
     *     path="/api/v1/service-records/{id}/cancel",
     *     summary="Cancel a service record",
     *     description="Mark a service record as cancelled with optional reason",
     *     operationId="cancelServiceRecord",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service record ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="reason", type="string", example="Customer requested cancellation", description="Cancellation reason")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service cancelled successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/VehicleServiceRecord")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Service record not found", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        $serviceRecord = $this->serviceRecordService->cancel($id, $request->input('reason'));

        return $this->successResponse(
            new VehicleServiceRecordResource($serviceRecord),
            __('customer::messages . service_cancelled')
        );
    }

    /**
     * Get vehicle service statistics
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{vehicleId}/service-statistics",
     *     summary="Get vehicle service statistics",
     *     description="Retrieve comprehensive service statistics for a specific vehicle",
     *     operationId="getVehicleServiceRecordStatistics",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="vehicleId",
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
     *                 @OA\Property(property="total_services", type="integer", example=10),
     *                 @OA\Property(property="total_cost", type="number", format="float", example=1250 . 00),
     *                 @OA\Property(property="average_cost", type="number", format="float", example=125 . 00),
     *                 @OA\Property(property="last_service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="services_by_type", type="object", example={"Oil Change": 5, "Brake Service": 3, "Tire Rotation": 2})
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function vehicleStatistics(int $vehicleId): JsonResponse
    {
        $statistics = $this->serviceRecordService->getVehicleStatistics($vehicleId);

        return $this->successResponse(
            $statistics,
            __('customer::messages . statistics_retrieved')
        );
    }

    /**
     * Get customer service statistics
     *
     * @OA\Get(
     *     path="/api/v1/customers/{customerId}/service-statistics",
     *     summary="Get customer service statistics",
     *     description="Retrieve comprehensive service statistics across all customer vehicles",
     *     operationId="getCustomerServiceStatistics",
     *     tags={"Service Records"},
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
     *                 @OA\Property(property="total_vehicles", type="integer", example=3),
     *                 @OA\Property(property="total_services", type="integer", example=25),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=3125 . 00),
     *                 @OA\Property(property="average_cost_per_service", type="number", format="float", example=125 . 00),
     *                 @OA\Property(property="last_service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="services_by_type", type="object", example={"Oil Change": 12, "Brake Service": 8, "Tire Rotation": 5})
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function customerStatistics(int $customerId): JsonResponse
    {
        $statistics = $this->serviceRecordService->getCustomerStatistics($customerId);

        return $this->successResponse(
            $statistics,
            __('customer::messages . statistics_retrieved')
        );
    }

    /**
     * Get vehicle service history summary (cross-branch)
     *
     * @OA\Get(
     *     path="/api/v1/vehicles/{vehicleId}/history-summary",
     *     summary="Get vehicle service history summary",
     *     description="Retrieve a comprehensive summary of service history across all branches",
     *     operationId="getVehicleServiceHistorySummary",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="vehicleId",
     *         in="path",
     *         required=true,
     *         description="Vehicle ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service history summary retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service history retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *
     *                 @OA\Property(property="vehicle_id", type="integer", example=1),
     *                 @OA\Property(property="total_services", type="integer", example=15),
     *                 @OA\Property(property="branches_serviced", type="array", @OA\Items(type="string"), example={"BRANCH-01", "BRANCH-02"}),
     *                 @OA\Property(property="first_service_date", type="string", format="date-time", example="2023-01-15T10:00:00Z"),
     *                 @OA\Property(property="last_service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=1875 . 00)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Vehicle not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */

    public function vehicleHistorySummary(int $vehicleId): JsonResponse
    {
        $summary = $this->serviceRecordService->getVehicleServiceHistorySummary($vehicleId);

        return $this->successResponse(
            $summary,
            __('customer::messages . service_history_retrieved')
        );
    }

    /**
     * Search service records
     *
     * @OA\Get(
     *     path="/api/v1/service-records/search",
     *     summary="Search service records",
     *     description="Search for service records by service number, vehicle registration, customer name, or service type",
     *     operationId="searchServiceRecords",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query (minimum 2 characters)",
     *
     *         @OA\Schema(type="string", example="Oil Change", minLength=2)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
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

        $serviceRecords = $this->serviceRecordService->search($request->input('query'));

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }

    /**
     * Get service records by date range
     *
     * @OA\Get(
     *     path="/api/v1/service-records/by-date-range",
     *     summary="Get service records by date range",
     *     description="Retrieve service records within a specific date range",
     *     operationId="getServiceRecordsByDateRange",
     *     tags={"Service Records"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=true,
     *         description="Start date (YYYY-MM-DD)",
     *
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         description="End date (YYYY-MM-DD)",
     *
     *         @OA\Schema(type="string", format="date", example="2024-12-31")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service records retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service records retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/VehicleServiceRecord")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    public function byDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $serviceRecords = $this->serviceRecordService->getByDateRange(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->successResponse(
            VehicleServiceRecordResource::collection($serviceRecords),
            __('customer::messages . service_records_retrieved')
        );
    }
}

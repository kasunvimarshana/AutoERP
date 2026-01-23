<?php

namespace App\Modules\CustomerManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\CustomerManagement\Http\Requests\StoreVehicleRequest;
use App\Modules\CustomerManagement\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class VehicleController extends BaseController
{
    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    #[OA\Get(
        path: "/api/v1/vehicles",
        summary: "List all vehicles",
        description: "Retrieve a paginated list of vehicles with optional filtering by customer, service due date, etc.",
        security: [["sanctum" => []]],
        tags: ["Vehicle Management"],
        parameters: [
            new OA\Parameter(name: "search", in: "query", description: "Search by VIN, make, model, or license plate", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "status", in: "query", description: "Filter by status", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "customer_id", in: "query", description: "Filter by customer ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "service_due", in: "query", description: "Filter vehicles with service due", schema: new OA\Schema(type: "boolean")),
            new OA\Parameter(name: "per_page", in: "query", description: "Results per page", schema: new OA\Schema(type: "integer", default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: "Vehicles retrieved successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'customer_id' => $request->input('customer_id'),
                'service_due' => $request->input('service_due'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $vehicles = $this->vehicleService->search($criteria);

            return $this->success($vehicles);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/vehicles",
        summary: "Register a new vehicle",
        description: "Create a new vehicle record and assign it to a customer",
        security: [["sanctum" => []]],
        tags: ["Vehicle Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["customer_id", "make", "model", "year", "vin"],
                properties: [
                    new OA\Property(property: "customer_id", type: "integer", example: 1),
                    new OA\Property(property: "make", type: "string", example: "Toyota"),
                    new OA\Property(property: "model", type: "string", example: "Camry"),
                    new OA\Property(property: "year", type: "integer", example: 2022),
                    new OA\Property(property: "vin", type: "string", example: "1HGBH41JXMN109186"),
                    new OA\Property(property: "license_plate", type: "string", example: "ABC-1234"),
                    new OA\Property(property: "color", type: "string", example: "Blue"),
                    new OA\Property(property: "current_mileage", type: "integer", example: 15000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Vehicle created successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;
            $data['ownership_start_date'] = now();

            $vehicle = $this->vehicleService->create($data);

            return $this->created($vehicle, 'Vehicle created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/vehicles/{id}",
        summary: "Get vehicle details",
        description: "Retrieve detailed vehicle information including current owner and ownership history",
        security: [["sanctum" => []]],
        tags: ["Vehicle Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Vehicle ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Vehicle retrieved successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Vehicle not found", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->findByIdOrFail($id);
            $vehicle->load(['currentCustomer', 'ownershipHistory']);

            return $this->success($vehicle);
        } catch (\Exception $e) {
            return $this->notFound('Vehicle not found');
        }
    }

    #[OA\Put(
        path: "/api/v1/vehicles/{id}",
        summary: "Update vehicle",
        description: "Update vehicle information",
        security: [["sanctum" => []]],
        tags: ["Vehicle Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Vehicle ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "color", type: "string", example: "Red"),
                    new OA\Property(property: "license_plate", type: "string", example: "XYZ-5678"),
                    new OA\Property(property: "current_mileage", type: "integer", example: 25000)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Vehicle updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Vehicle not found", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $vehicle = $this->vehicleService->update($id, $request->all());

            return $this->success($vehicle, 'Vehicle updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/vehicles/{id}/transfer-ownership",
        summary: "Transfer vehicle ownership",
        description: "Transfer vehicle to a new customer with complete ownership history tracking",
        security: [["sanctum" => []]],
        tags: ["Vehicle Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Vehicle ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["new_customer_id"],
                properties: [
                    new OA\Property(property: "new_customer_id", type: "integer", example: 2, description: "ID of the new owner"),
                    new OA\Property(property: "reason", type: "string", enum: ["sale", "gift", "trade", "inheritance", "other"], example: "sale"),
                    new OA\Property(property: "notes", type: "string", example: "Vehicle sold to new owner")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ownership transferred successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Vehicle not found", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function transferOwnership(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'new_customer_id' => 'required|exists:customers,id',
                'reason' => 'nullable|in:sale,gift,trade,inheritance,other',
                'notes' => 'nullable|string',
            ]);

            $vehicle = $this->vehicleService->transferOwnership(
                $id,
                $request->input('new_customer_id'),
                $request->only(['reason', 'notes'])
            );

            return $this->success($vehicle, 'Vehicle ownership transferred successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/vehicles/{id}/update-mileage",
        summary: "Update vehicle mileage",
        description: "Record new mileage reading for a vehicle",
        security: [["sanctum" => []]],
        tags: ["Vehicle Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Vehicle ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["mileage"],
                properties: [
                    new OA\Property(property: "mileage", type: "integer", example: 30000, description: "New mileage reading")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Mileage updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Vehicle not found", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function updateMileage(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'mileage' => 'required|numeric|min:0',
            ]);

            $vehicle = $this->vehicleService->updateMileage($id, $request->input('mileage'));

            return $this->success($vehicle, 'Vehicle mileage updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}

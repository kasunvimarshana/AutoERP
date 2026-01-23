<?php

namespace App\Modules\CustomerManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\CustomerManagement\Http\Requests\StoreCustomerRequest;
use App\Modules\CustomerManagement\Http\Requests\UpdateCustomerRequest;
use App\Modules\CustomerManagement\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CustomerController extends BaseController
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    #[OA\Get(
        path: "/api/v1/customers",
        summary: "List all customers",
        description: "Retrieve a paginated list of customers with optional search and filtering. Results are automatically filtered by tenant.",
        security: [["sanctum" => []]],
        tags: ["Customer Management"],
        parameters: [
            new OA\Parameter(name: "search", in: "query", description: "Search by name, email, or phone", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "status", in: "query", description: "Filter by status", schema: new OA\Schema(type: "string", enum: ["active", "inactive"])),
            new OA\Parameter(name: "customer_type", in: "query", description: "Filter by customer type", schema: new OA\Schema(type: "string", enum: ["individual", "business"])),
            new OA\Parameter(name: "per_page", in: "query", description: "Results per page", schema: new OA\Schema(type: "integer", default: 15))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Customers retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Success"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "data",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "customer_type", type: "string", example: "individual"),
                                            new OA\Property(property: "first_name", type: "string", example: "John"),
                                            new OA\Property(property: "last_name", type: "string", example: "Doe"),
                                            new OA\Property(property: "email", type: "string", example: "john@example.com"),
                                            new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                                            new OA\Property(property: "status", type: "string", example: "active")
                                        ],
                                        type: "object"
                                    )
                                ),
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "per_page", type: "integer", example: 15),
                                new OA\Property(property: "total", type: "integer", example: 100)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 500, description: "Server error", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $criteria = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'customer_type' => $request->input('customer_type'),
                'tenant_id' => $request->user()->tenant_id ?? null,
                'per_page' => $request->input('per_page', 15),
            ];

            $customers = $this->customerService->search($criteria);

            return $this->success($customers);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/customers",
        summary: "Create a new customer",
        description: "Creates a new customer record (individual or business type)",
        security: [["sanctum" => []]],
        tags: ["Customer Management"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["customer_type", "first_name", "email", "phone"],
                properties: [
                    new OA\Property(property: "customer_type", type: "string", enum: ["individual", "business"], example: "individual"),
                    new OA\Property(property: "first_name", type: "string", example: "John"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                    new OA\Property(property: "company_name", type: "string", example: "ABC Corp", description: "Required for business customers"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                    new OA\Property(property: "address_line1", type: "string", example: "123 Main St"),
                    new OA\Property(property: "address_line2", type: "string", example: "Apt 4B"),
                    new OA\Property(property: "city", type: "string", example: "New York"),
                    new OA\Property(property: "state", type: "string", example: "NY"),
                    new OA\Property(property: "postal_code", type: "string", example: "10001"),
                    new OA\Property(property: "country", type: "string", example: "US"),
                    new OA\Property(property: "notes", type: "string", example: "VIP customer")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Customer created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = $request->user()->tenant_id ?? null;

            $customer = $this->customerService->create($data);

            return $this->created($customer, 'Customer created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/customers/{id}",
        summary: "Get customer details",
        description: "Retrieve detailed information about a specific customer including their vehicles",
        security: [["sanctum" => []]],
        tags: ["Customer Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Customer retrieved successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Customer not found", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function show(int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->findByIdOrFail($id);
            $customer->load('vehicles');

            return $this->success($customer);
        } catch (\Exception $e) {
            return $this->notFound('Customer not found');
        }
    }

    #[OA\Put(
        path: "/api/v1/customers/{id}",
        summary: "Update customer",
        description: "Update customer information",
        security: [["sanctum" => []]],
        tags: ["Customer Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "first_name", type: "string", example: "John"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Customer updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Customer not found", ref: "#/components/schemas/ErrorResponse"),
            new OA\Response(response: 422, description: "Validation error", ref: "#/components/schemas/ValidationErrorResponse")
        ]
    )]
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->update($id, $request->validated());

            return $this->success($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Delete(
        path: "/api/v1/customers/{id}",
        summary: "Delete customer",
        description: "Delete a customer from the system",
        security: [["sanctum" => []]],
        tags: ["Customer Management"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Customer ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Customer deleted successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 404, description: "Customer not found", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->customerService->delete($id);

            return $this->success(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/customers/upcoming-services",
        summary: "Get customers with upcoming services",
        description: "Retrieve list of customers who have services scheduled in the near future",
        security: [["sanctum" => []]],
        tags: ["Customer Management"],
        responses: [
            new OA\Response(response: 200, description: "Customers retrieved successfully", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", ref: "#/components/schemas/ErrorResponse")
        ]
    )]
    public function upcomingServices(): JsonResponse
    {
        try {
            $customers = $this->customerService->getWithUpcomingServices();

            return $this->success($customers);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}

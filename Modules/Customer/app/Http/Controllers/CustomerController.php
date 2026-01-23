<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Requests\StoreCustomerRequest;
use Modules\Customer\Requests\UpdateCustomerRequest;
use Modules\Customer\Resources\CustomerResource;
use Modules\Customer\Services\CustomerService;
use OpenApi\Attributes as OA;

/**
 * Customer Controller
 *
 * Handles HTTP requests for Customer operations
 * Follows Controller → Service → Repository pattern
 */
class CustomerController extends Controller
{
    /**
     * CustomerController constructor
     */
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers
     *
     * @OA\Get(
     *     path="/api/v1/customers",
     *     summary="List all customers",
     *     description="Get a paginated list of all customers with optional filtering",
     *     operationId="getCustomers",
     *     tags={"Customers"},
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
     *         description="Customers retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customers retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Customer")
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

        $customers = $this->customerService->getAll($filters);

        return $this->successResponse(
            CustomerResource::collection($customers),
            __('customer::messages.customers_retrieved')
        );
    }

    /**
     * Store a newly created customer
     *
     * @OA\Post(
     *     path="/api/v1/customers",
     *     summary="Create a new customer",
     *     description="Create a new customer record",
     *     operationId="createCustomer",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"first_name", "last_name"},
     *
     *             @OA\Property(property="first_name", type="string", example="John", description="Customer's first name"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Customer's last name"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer's email"),
     *             @OA\Property(property="phone", type="string", example="+1234567890", description="Customer's phone number"),
     *             @OA\Property(property="mobile", type="string", example="+1234567890", description="Customer's mobile number"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Main St", description="Address line 1"),
     *             @OA\Property(property="address_line_2", type="string", example="Apt 4B", description="Address line 2"),
     *             @OA\Property(property="city", type="string", example="New York", description="City"),
     *             @OA\Property(property="state", type="string", example="NY", description="State"),
     *             @OA\Property(property="postal_code", type="string", example="10001", description="Postal code"),
     *             @OA\Property(property="country", type="string", example="USA", description="Country"),
     *             @OA\Property(property="customer_type", type="string", enum={"individual", "corporate"}, example="individual", description="Customer type"),
     *             @OA\Property(property="company_name", type="string", example="Acme Corp", description="Company name (required for corporate customers)"),
     *             @OA\Property(property="tax_id", type="string", example="12-3456789", description="Tax ID"),
     *             @OA\Property(property="receive_notifications", type="boolean", example=true, description="Receive notifications flag"),
     *             @OA\Property(property="receive_marketing", type="boolean", example=false, description="Receive marketing flag"),
     *             @OA\Property(property="notes", type="string", example="VIP customer", description="Additional notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());

        return $this->createdResponse(
            new CustomerResource($customer),
            __('customer::messages.customer_created')
        );
    }

    /**
     * Display the specified customer
     *
     * @OA\Get(
     *     path="/api/v1/customers/{id}",
     *     summary="Get customer by ID",
     *     description="Retrieve a specific customer's details by ID",
     *     operationId="getCustomerById",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->getById($id);

        return $this->successResponse(
            new CustomerResource($customer),
            __('customer::messages.customer_retrieved')
        );
    }

    /**
     * Update the specified customer
     *
     * @OA\Put(
     *     path="/api/v1/customers/{id}",
     *     summary="Update customer",
     *     description="Update an existing customer's information",
     *     operationId="updateCustomer",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="first_name", type="string", example="John", description="Customer's first name"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Customer's last name"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Customer's email"),
     *             @OA\Property(property="phone", type="string", example="+1234567890", description="Customer's phone number"),
     *             @OA\Property(property="mobile", type="string", example="+1234567890", description="Customer's mobile number"),
     *             @OA\Property(property="address_line_1", type="string", example="123 Main St", description="Address line 1"),
     *             @OA\Property(property="address_line_2", type="string", example="Apt 4B", description="Address line 2"),
     *             @OA\Property(property="city", type="string", example="New York", description="City"),
     *             @OA\Property(property="state", type="string", example="NY", description="State"),
     *             @OA\Property(property="postal_code", type="string", example="10001", description="Postal code"),
     *             @OA\Property(property="country", type="string", example="USA", description="Country"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active", description="Customer status"),
     *             @OA\Property(property="customer_type", type="string", enum={"individual", "corporate"}, example="individual", description="Customer type"),
     *             @OA\Property(property="company_name", type="string", example="Acme Corp", description="Company name (required for corporate customers)"),
     *             @OA\Property(property="tax_id", type="string", example="12-3456789", description="Tax ID"),
     *             @OA\Property(property="receive_notifications", type="boolean", example=true, description="Receive notifications flag"),
     *             @OA\Property(property="receive_marketing", type="boolean", example=false, description="Receive marketing flag"),
     *             @OA\Property(property="notes", type="string", example="VIP customer", description="Additional notes")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = $this->customerService->update($id, $request->validated());

        return $this->successResponse(
            new CustomerResource($customer),
            __('customer::messages.customer_updated')
        );
    }

    /**
     * Remove the specified customer
     *
     * @OA\Delete(
     *     path="/api/v1/customers/{id}",
     *     summary="Delete customer",
     *     description="Delete a customer from the system",
     *     operationId="deleteCustomer",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->customerService->delete($id);

        return $this->successResponse(
            null,
            __('customer::messages.customer_deleted')
        );
    }

    /**
     * Get customer with vehicles
     *
     * @OA\Get(
     *     path="/api/v1/customers/{id}/vehicles",
     *     summary="Get customer with vehicles",
     *     description="Retrieve a customer with all their associated vehicles",
     *     operationId="getCustomerWithVehicles",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer with vehicles retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function withVehicles(int $id): JsonResponse
    {
        $customer = $this->customerService->getWithVehicles($id);

        return $this->successResponse(
            new CustomerResource($customer),
            __('customer::messages.customer_retrieved')
        );
    }

    /**
     * Search customers
     *
     * @OA\Get(
     *     path="/api/v1/customers/search",
     *     summary="Search customers",
     *     description="Search for customers by name, email, phone, or customer number",
     *     operationId="searchCustomers",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query (minimum 2 characters)",
     *
     *         @OA\Schema(type="string", example="John", minLength=2)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customers retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Customer")
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

        $customers = $this->customerService->search($request->input('query'));

        return $this->successResponse(
            CustomerResource::collection($customers),
            __('customer::messages.customers_retrieved')
        );
    }

    /**
     * Get customer statistics
     *
     * @OA\Get(
     *     path="/api/v1/customers/{id}/statistics",
     *     summary="Get customer statistics",
     *     description="Retrieve comprehensive statistics for a customer including vehicle count, service history, and spending",
     *     operationId="getCustomerStatistics",
     *     tags={"Customers"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
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
     *                 @OA\Property(property="total_service_records", type="integer", example=15),
     *                 @OA\Property(property="total_spent", type="number", format="float", example=2500.00),
     *                 @OA\Property(property="last_service_date", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="active_vehicles", type="integer", example=2)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error")),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function statistics(int $id): JsonResponse
    {
        $statistics = $this->customerService->getStatistics($id);

        return $this->successResponse(
            $statistics,
            __('customer::messages.statistics_retrieved')
        );
    }
}

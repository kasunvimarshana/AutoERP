<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Sales\Http\Requests\StoreCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Services\CustomerService;

/**
 * Customer Controller
 *
 * Handles HTTP requests for customer/client operations including CRM functionality.
 */
class CustomerController extends BaseController
{
    public function __construct(
        protected CustomerService $service
    ) {}

    /**
     * @OA\Get(
     *     path="/api/sales/customers",
     *     summary="List customers",
     *     description="Retrieve paginated list of customers/clients with optional filtering",
     *     operationId="customersIndex",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive"}, example="active")
     *     ),
     *     @OA\Parameter(
     *         name="tier",
     *         in="query",
     *         description="Filter by customer tier",
     *         required=false,
     *         @OA\Schema(type="string", enum={"standard", "premium", "vip"}, example="premium")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in customer name, email, and code",
     *         required=false,
     *         @OA\Schema(type="string", example="Acme Corp")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customers retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Customer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'tier', 'search',
            'sort_by', 'sort_order', 'per_page',
        ]);

        $customers = $this->service->getAll($filters, (int) ($request->input('per_page', 15)));

        return $this->successResponse($customers, 'Customers retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/sales/customers/{id}",
     *     summary="Get customer details",
     *     description="Retrieve detailed information about a specific customer",
     *     operationId="customerShow",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->service->findById($id);

        if (! $customer) {
            return $this->errorResponse('Customer not found', 404);
        }

        return $this->successResponse($customer, 'Customer retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/api/sales/customers",
     *     summary="Create customer",
     *     description="Create a new customer/client",
     *     operationId="customerStore",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Customer data",
     *         @OA\JsonContent(ref="#/components/schemas/CustomerRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->service->create($request->validated());

            return $this->successResponse($customer, 'Customer created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/sales/customers/{id}",
     *     summary="Update customer",
     *     description="Update an existing customer",
     *     operationId="customerUpdate",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated customer data",
     *         @OA\JsonContent(ref="#/components/schemas/CustomerRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        try {
            $customer = $this->service->update($id, $request->validated());

            return $this->successResponse($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/sales/customers/{id}",
     *     summary="Delete customer",
     *     description="Delete a customer (soft delete)",
     *     operationId="customerDestroy",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=400, description="Cannot delete customer", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/sales/customers/{id}/statistics",
     *     summary="Get customer statistics",
     *     description="Get sales statistics for a specific customer",
     *     operationId="customerStatistics",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer statistics retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_orders", type="integer", example=50),
     *                 @OA\Property(property="total_revenue", type="number", format="float", example=125000.50),
     *                 @OA\Property(property="average_order_value", type="number", format="float", example=2500.01),
     *                 @OA\Property(property="outstanding_balance", type="number", format="float", example=5000.00),
     *                 @OA\Property(property="available_credit", type="number", format="float", example=45000.00),
     *                 @OA\Property(property="credit_utilization", type="number", format="float", example=10.00)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function statistics(int $id): JsonResponse
    {
        try {
            $statistics = $this->service->getStatistics($id);

            return $this->successResponse($statistics, 'Customer statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/customers/{id}/activate",
     *     summary="Activate customer",
     *     description="Activate a customer account",
     *     operationId="customerActivate",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer activated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $customer = $this->service->activate($id);

            return $this->successResponse($customer, 'Customer activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/sales/customers/{id}/deactivate",
     *     summary="Deactivate customer",
     *     description="Deactivate a customer account",
     *     operationId="customerDeactivate",
     *     tags={"Sales-Customers"},
     *     security={{"sanctum_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deactivated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer deactivated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Customer")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
     * )
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $customer = $this->service->deactivate($id);

            return $this->successResponse($customer, 'Customer deactivated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}

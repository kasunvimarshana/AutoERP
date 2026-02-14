<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\DTOs\CustomerDTO;
use App\Modules\Customer\Http\Requests\StoreCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management endpoints"
 * )
 */
class CustomerController extends BaseController
{
    protected CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers",
     *     tags={"Customers"},
     *     summary="List all customers",
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
     *             @OA\Property(property="message", type="string", example="Customers retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $customers = $this->service->paginate($perPage, ['tenant', 'branch', 'vehicles']);
            
            return $this->successResponse($customers, 'Customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers",
     *     tags={"Customers"},
     *     summary="Create a new customer",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "branch_id", "code", "name", "customer_type"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="branch_id", type="string", format="uuid"),
     *             @OA\Property(property="code", type="string", example="CUST-001"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="mobile", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="tax_id", type="string"),
     *             @OA\Property(property="customer_type", type="string", enum={"individual", "business"}),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
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
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $dto = CustomerDTO::fromArray($request->validated());
            $customer = $this->service->createCustomer($dto);
            
            return $this->successResponse($customer, 'Customer created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Get a specific customer",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
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
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $customer = $this->service->findById($id, ['tenant', 'branch', 'vehicles']);
            
            if (!$customer) {
                return $this->errorResponse('Customer not found', 404);
            }
            
            return $this->successResponse($customer, 'Customer retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Update a customer",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="mobile", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="city", type="string"),
     *             @OA\Property(property="state", type="string"),
     *             @OA\Property(property="country", type="string"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="tax_id", type="string"),
     *             @OA\Property(property="customer_type", type="string", enum={"individual", "business"}),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="settings", type="object")
     *         )
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
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        try {
            $dto = CustomerDTO::fromArray($request->validated());
            $result = $this->service->updateCustomer($id, $dto);
            
            if (!$result) {
                return $this->errorResponse('Customer not found or update failed', 404);
            }
            
            $customer = $this->service->findById($id, ['tenant', 'branch', 'vehicles']);
            
            return $this->successResponse($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Delete a customer",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->deleteCustomer($id);
            
            if (!$result) {
                return $this->errorResponse('Customer not found or delete failed', 404);
            }
            
            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers/{id}/restore",
     *     tags={"Customers"},
     *     summary="Restore a soft-deleted customer",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer restored successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $result = $this->service->restore($id);
            
            if (!$result) {
                return $this->errorResponse('Customer not found or restore failed', 404);
            }
            
            return $this->successResponse(null, 'Customer restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{id}/vehicles",
     *     tags={"Customers"},
     *     summary="Get all vehicles for a customer",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer vehicles retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Vehicle"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function vehicles(string $id): JsonResponse
    {
        try {
            $vehicles = $this->service->getCustomerVehicles($id);
            
            if ($vehicles === null) {
                return $this->errorResponse('Customer not found', 404);
            }
            
            return $this->successResponse($vehicles, 'Customer vehicles retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{id}/history",
     *     tags={"Customers"},
     *     summary="Get customer history (orders, invoices, vehicles)",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer history retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="orders", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="invoices", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="vehicles", type="array", @OA\Items(ref="#/components/schemas/Vehicle"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function history(string $id): JsonResponse
    {
        try {
            $history = $this->service->getCustomerHistory($id);
            
            if (empty($history)) {
                return $this->errorResponse('Customer not found', 404);
            }
            
            return $this->successResponse($history, 'Customer history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}

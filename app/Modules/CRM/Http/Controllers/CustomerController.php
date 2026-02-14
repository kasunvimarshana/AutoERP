<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\CreateCustomerRequest;
use App\Modules\CRM\Http\Requests\UpdateCustomerRequest;
use App\Modules\CRM\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer management endpoints"
 * )
 */
class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers",
     *     summary="Get all customers",
     *     tags={"Customers"},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $customers = $this->customerService->getAll($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers",
     *     summary="Create a new customer",
     *     tags={"Customers"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/CreateCustomerRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully"
     *     )
     * )
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{id}",
     *     summary="Get customer by ID",
     *     tags={"Customers"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->getCustomerProfile($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/customers/{id}",
     *     summary="Update customer",
     *     tags={"Customers"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/UpdateCustomerRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully"
     *     )
     * )
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $this->customerService->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/customers/{id}",
     *     summary="Delete customer",
     *     tags={"Customers"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Customer ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->customerService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/search",
     *     summary="Search customers",
     *     tags={"Customers"},
     *
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $customers = $this->customerService->searchCustomers($query);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }
}

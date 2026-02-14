<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
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
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        $customers = $this->service->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customers",
     *     tags={"Customers"},
     *     summary="Create a new customer",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "email"},
     *             @OA\Property(property="type", type="string", enum={"individual", "business"}),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="company_name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        
        $validated = $request->validate([
            'type' => 'required|in:individual,business',
            'first_name' => 'required_if:type,individual|string|max:255',
            'last_name' => 'required_if:type,individual|string|max:255',
            'company_name' => 'required_if:type,business|string|max:255',
            'email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('customers', 'email')->where('tenant_id', $tenantId)
            ],
            'phone' => 'required|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0',
        ]);

        $customer = $this->service->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Get customer by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
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
        $customer = $this->service->find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer->load(['contacts', 'addresses', 'tags'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Update customer",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:customers,email,' . $id,
            'phone' => 'sometimes|string|max:20',
            'credit_limit' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $customer = $this->service->update($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/customers/{id}",
     *     tags={"Customers"},
     *     summary="Delete customer",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/search",
     *     tags={"Customers"},
     *     summary="Search customers",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);
        
        $results = $this->service->searchCustomers($request->get('q'));

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}

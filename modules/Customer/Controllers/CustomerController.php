<?php

namespace Modules\Customer\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Services\CustomerService;

// use Modules\Customer\Requests\StoreCustomerRequest;
// use Modules\Customer\Requests\UpdateCustomerRequest;
// use Modules\Customer\Resources\CustomerResource;

/**
 * Customer Controller
 *
 * Handles HTTP requests for customer operations.
 * Follows the Controller -> Service -> Repository pattern.
 *
 * Controller Responsibilities:
 * - Handle HTTP requests and responses
 * - Validate input (via Form Requests)
 * - Delegate to service layer
 * - Transform responses (via Resources)
 * - Handle authorization (via Policies)
 */
class CustomerController // extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Authorization check
            // $this->authorize('viewAny', Customer::class);

            $perPage = $request->input('per_page', 15);
            $searchTerm = $request->input('search');

            if ($searchTerm) {
                $customers = $this->customerService->searchCustomers($searchTerm, $perPage);
            } else {
                $customers = $this->customerService->getRepository()->paginate($perPage);
            }

            return response()->json([
                'success' => true,
                'data' => $customers,
                // 'data' => CustomerResource::collection($customers),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Authorization check
            // $this->authorize('create', Customer::class);

            // Validation would be done via StoreCustomerRequest
            $data = $request->all();

            $customer = $this->customerService->createCustomer($data);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $customer,
                // 'data' => new CustomerResource($customer),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomer($id);

            // Authorization check
            // $this->authorize('view', $customer);

            return response()->json([
                'success' => true,
                'data' => $customer,
                // 'data' => new CustomerResource($customer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomer($id);

            // Authorization check
            // $this->authorize('update', $customer);

            // Validation would be done via UpdateCustomerRequest
            $data = $request->all();

            $customer = $this->customerService->updateCustomer($id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer,
                // 'data' => new CustomerResource($customer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->getCustomer($id);

            // Authorization check
            // $this->authorize('delete', $customer);

            $deleted = $this->customerService->deleteCustomer($id);

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            // Authorization check
            // $this->authorize('restore', Customer::class);

            $restored = $this->customerService->restoreCustomer($id);

            return response()->json([
                'success' => true,
                'message' => 'Customer restored successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore customer',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get customer statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            // Authorization check
            // $this->authorize('viewStatistics', Customer::class);

            $statistics = $this->customerService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Merge two customer records.
     */
    public function merge(Request $request): JsonResponse
    {
        try {
            // Authorization check
            // $this->authorize('merge', Customer::class);

            $primaryId = $request->input('primary_customer_id');
            $duplicateId = $request->input('duplicate_customer_id');

            $customer = $this->customerService->mergeCustomers($primaryId, $duplicateId);

            return response()->json([
                'success' => true,
                'message' => 'Customers merged successfully',
                'data' => $customer,
                // 'data' => new CustomerResource($customer),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge customers',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}

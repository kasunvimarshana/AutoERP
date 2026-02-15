<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CRM\CustomerResource;
use App\Modules\CRM\Services\CRMService;
use App\Http\Requests\CRM\StoreCustomerRequest;
use App\Http\Requests\CRM\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends BaseController
{
    protected CRMService $crmService;

    public function __construct(CRMService $crmService)
    {
        $this->crmService = $crmService;
    }

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $customers = $this->crmService->getAll([], $perPage);

            return $this->paginatedResponse(
                CustomerResource::collection($customers),
                'Customers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $customer = $this->crmService->createCustomer($validated);

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create customer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $customer = $this->crmService->getById($id);

            if (!$customer) {
                return $this->errorResponse('Customer not found', [], 404);
            }

            $this->authorize('view', $customer);

            return $this->successResponse(
                new CustomerResource($customer->load(['addresses', 'contacts', 'notes'])),
                'Customer retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = auth()->id();

            $customer = $this->crmService->updateCustomer($id, $validated);

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update customer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $customer = $this->crmService->getById($id);

            if (!$customer) {
                return $this->errorResponse('Customer not found', [], 404);
            }

            $this->authorize('delete', $customer);

            $this->crmService->delete($id);

            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete customer: ' . $e->getMessage());
        }
    }

    /**
     * Search customers.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $filters = $request->except(['q', 'page', 'per_page']);

            $customers = $this->crmService->searchCustomers($query, $filters);

            return $this->successResponse(
                CustomerResource::collection($customers),
                'Search results retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Get customer statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->crmService->getCustomerStatistics();

            return $this->successResponse($stats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}

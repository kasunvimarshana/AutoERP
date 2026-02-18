<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\CRM\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{
    public function __construct(
        protected CustomerService $service
    ) {}

    /**
     * List all customers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $customers = $this->service->getAllCustomers($perPage);

            return $this->successResponse($customers);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers: '.$e->getMessage());
        }
    }

    /**
     * Create a new customer
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $customer = $this->service->createCustomer($request->all());

            return $this->successResponse($customer, 'Customer created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create customer: '.$e->getMessage());
        }
    }

    /**
     * Get a specific customer
     */
    public function show(int $id): JsonResponse
    {
        try {
            $customer = $this->service->getCustomer($id);

            if (! $customer) {
                return $this->errorResponse('Customer not found', null, 404);
            }

            return $this->successResponse($customer);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer: '.$e->getMessage());
        }
    }

    /**
     * Update a customer
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $customer = $this->service->updateCustomer($id, $request->all());

            if (! $customer) {
                return $this->errorResponse('Customer not found', 404);
            }

            return $this->successResponse($customer, 'Customer updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update customer: '.$e->getMessage());
        }
    }

    /**
     * Delete a customer
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteCustomer($id);

            if (! $deleted) {
                return $this->errorResponse('Customer not found', 404);
            }

            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete customer: '.$e->getMessage());
        }
    }

    /**
     * Search customers
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $term = $request->get('q', '');
            $perPage = $request->get('per_page', 15);

            if (empty($term)) {
                return $this->errorResponse('Search term is required', 400);
            }

            $customers = $this->service->searchCustomers($term, $perPage);

            return $this->successResponse($customers);
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed: '.$e->getMessage());
        }
    }
}

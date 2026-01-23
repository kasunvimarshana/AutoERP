<?php

namespace App\Modules\CustomerManagement\Http\Controllers;

use App\Core\Base\BaseController;
use App\Modules\CustomerManagement\Http\Requests\StoreCustomerRequest;
use App\Modules\CustomerManagement\Http\Requests\UpdateCustomerRequest;
use App\Modules\CustomerManagement\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of customers
     */
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

    /**
     * Store a newly created customer
     */
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

    /**
     * Display the specified customer
     */
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

    /**
     * Update the specified customer
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        try {
            $customer = $this->customerService->update($id, $request->validated());

            return $this->success($customer, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->customerService->delete($id);

            return $this->success(null, 'Customer deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get customers with upcoming services
     */
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

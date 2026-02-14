<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Core\BaseController;
use App\Modules\CRM\Services\CustomerService;
use App\Modules\CRM\Requests\CreateCustomerRequest;
use App\Modules\CRM\Requests\UpdateCustomerRequest;
use App\Modules\CRM\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;

/**
 * Customer Controller
 * 
 * Handles CRUD operations for customer management
 * 
 * @package App\Modules\CRM\Http\Controllers
 */
class CustomerController extends BaseController
{
    protected CustomerService $service;

    public function __construct(CustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of customers
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $customers = $this->service->getAllCustomers();
        return $this->collection(CustomerResource::collection($customers));
    }

    /**
     * Store a newly created customer
     *
     * @param CreateCustomerRequest $request
     * @return JsonResponse
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->createCustomer($request->validated());
        return $this->created(new CustomerResource($customer));
    }

    /**
     * Display the specified customer
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->service->getCustomer($id);
        
        if (!$customer) {
            return $this->notFound();
        }
        
        return $this->resource(new CustomerResource($customer));
    }

    /**
     * Update the specified customer
     *
     * @param UpdateCustomerRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateCustomer($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Customer updated successfully');
    }

    /**
     * Remove the specified customer
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteCustomer($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}

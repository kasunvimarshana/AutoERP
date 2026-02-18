<?php

namespace Modules\Sales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Http\Requests\StoreCustomerRequest;
use Modules\Sales\Http\Requests\UpdateCustomerRequest;
use Modules\Sales\Http\Resources\CustomerResource;
use Modules\Sales\Repositories\Contracts\CustomerRepositoryInterface;

/**
 * Customer API Controller
 *
 * Handles API requests for customer management.
 */
class CustomerController extends Controller
{
    /**
     * CustomerController constructor.
     */
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $customers = $this->customerRepository->paginate($perPage);

        return CustomerResource::collection($customers)
            ->response();
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerRepository->create($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerRepository->findById($id);

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }

        return (new CustomerResource($customer))->response();
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = $this->customerRepository->update($id, $request->validated());

        return (new CustomerResource($customer))->response();
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->customerRepository->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Customer deleted successfully',
        ], 200);
    }

    /**
     * Search customers.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $customers = $this->customerRepository->search($query);

        return CustomerResource::collection($customers)
            ->response();
    }
}

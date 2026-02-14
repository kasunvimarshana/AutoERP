<?php

namespace App\Http\Controllers\Api\V1\CRM;

use App\Http\Controllers\Api\ApiController;
use App\Modules\CRM\Models\Customer;
use App\Modules\CRM\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends ApiController
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
        $query = Customer::query()->with(['organization', 'currency', 'priceList']);

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        // Search
        if ($request->has('search')) {
            $customers = $this->customerService->searchCustomers($request->search);
        } else {
            $customers = $query->active()->paginate($request->get('per_page', 15));
        }

        return $this->success($customers, 'Customers retrieved successfully');
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'first_name' => 'required_if:type,individual|string|max:100',
            'last_name' => 'required_if:type,individual|string|max:100',
            'company_name' => 'required_if:type,company|string|max:200',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'currency_id' => 'nullable|exists:currencies,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;

        $customer = $this->customerService->createCustomer($validated);

        return $this->success($customer, 'Customer created successfully', 201);
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['contacts', 'organization', 'currency', 'priceList'])
            ->findOrFail($id);
        
        return $this->success($customer, 'Customer retrieved successfully');
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:individual,company',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'company_name' => 'sometimes|string|max:200',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'currency_id' => 'nullable|exists:currencies,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'status' => 'sometimes|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $customer = $this->customerService->updateCustomer($id, $validated);

        return $this->success($customer, 'Customer updated successfully');
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->customerService->deleteCustomer($id);
        
        return $this->success(null, 'Customer deleted successfully');
    }

    /**
     * Check customer credit limit.
     */
    public function checkCreditLimit(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'additional_amount' => 'required|numeric|min:0',
        ]);

        try {
            $available = $this->customerService->checkCreditLimit($id, $validated['additional_amount']);

            return $this->success([
                'credit_available' => $available,
            ], 'Credit limit check completed');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}

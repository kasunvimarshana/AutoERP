<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\CRM\Http\Requests\StoreCustomerRequest;
use Modules\CRM\Http\Requests\UpdateCustomerRequest;
use Modules\CRM\Http\Resources\ContactResource;
use Modules\CRM\Http\Resources\CustomerResource;
use Modules\CRM\Http\Resources\OpportunityResource;
use Modules\CRM\Models\Customer;
use Modules\CRM\Repositories\CustomerRepository;
use Modules\CRM\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->with(['organization', 'primaryContact']);

        if ($request->has('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $customers = $query->paginate($perPage);

        return ApiResponse::paginated(
            $customers->setCollection(
                $customers->getCollection()->map(fn ($customer) => new CustomerResource($customer))
            ),
            'Customers retrieved successfully'
        );
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $customer = $this->customerService->createCustomer($data);
        $customer->load(['organization', 'primaryContact']);

        return ApiResponse::created(
            new CustomerResource($customer),
            'Customer created successfully'
        );
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $customer->load(['organization', 'primaryContact', 'contacts', 'opportunities']);

        return ApiResponse::success(
            new CustomerResource($customer),
            'Customer retrieved successfully'
        );
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $data = $request->validated();

        $customer = DB::transaction(function () use ($customer, $data) {
            return $this->customerRepository->update($customer->id, $data);
        });

        $customer->load(['organization', 'primaryContact']);

        return ApiResponse::success(
            new CustomerResource($customer),
            'Customer updated successfully'
        );
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        DB::transaction(function () use ($customer) {
            $this->customerRepository->delete($customer->id);
        });

        return ApiResponse::success(
            null,
            'Customer deleted successfully'
        );
    }

    /**
     * Get contacts for the specified customer.
     */
    public function contacts(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('view', $customer);

        $contacts = $customer->contacts()
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success(
            ContactResource::collection($contacts),
            'Customer contacts retrieved successfully'
        );
    }

    /**
     * Get opportunities for the specified customer.
     */
    public function opportunities(Customer $customer, Request $request): JsonResponse
    {
        $this->authorize('view', $customer);

        $query = $customer->opportunities();

        if ($request->has('stage')) {
            $query->where('stage', $request->stage);
        }

        $perPage = $request->get('per_page', 15);
        $opportunities = $query->paginate($perPage);

        return ApiResponse::paginated(
            $opportunities->setCollection(
                $opportunities->getCollection()->map(fn ($opp) => new OpportunityResource($opp))
            ),
            'Customer opportunities retrieved successfully'
        );
    }
}

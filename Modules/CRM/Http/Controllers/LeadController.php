<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\CRM\Enums\LeadStatus;
use Modules\CRM\Http\Requests\StoreLeadRequest;
use Modules\CRM\Http\Requests\UpdateLeadRequest;
use Modules\CRM\Http\Resources\CustomerResource;
use Modules\CRM\Http\Resources\LeadResource;
use Modules\CRM\Models\Lead;
use Modules\CRM\Repositories\LeadRepository;
use Modules\CRM\Services\LeadConversionService;

class LeadController extends Controller
{
    public function __construct(
        private LeadRepository $leadRepository,
        private LeadConversionService $leadConversionService
    ) {}

    /**
     * Display a listing of leads.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $filters = $request->only([
            'status',
            'organization_id',
            'assigned_to',
            'source',
            'search'
        ]);

        if ($request->has('converted')) {
            $filters['converted'] = $request->boolean('converted');
        }

        $perPage = $request->get('per_page', 15);
        $leads = $this->leadRepository->findWithFilters($filters, $perPage);

        return ApiResponse::paginated(
            $leads->setCollection(
                $leads->getCollection()->map(fn ($lead) => new LeadResource($lead))
            ),
            'Leads retrieved successfully'
        );
    }

    /**
     * Store a newly created lead.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $this->authorize('create', Lead::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['status'] = $data['status'] ?? LeadStatus::NEW;

        $lead = DB::transaction(function () use ($data) {
            return $this->leadRepository->create($data);
        });

        $lead->load(['organization']);

        return ApiResponse::created(
            new LeadResource($lead),
            'Lead created successfully'
        );
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->load(['organization']);

        return ApiResponse::success(
            new LeadResource($lead),
            'Lead retrieved successfully'
        );
    }

    /**
     * Update the specified lead.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $data = $request->validated();

        $lead = DB::transaction(function () use ($lead, $data) {
            return $this->leadRepository->update($lead->id, $data);
        });

        $lead->load(['organization']);

        return ApiResponse::success(
            new LeadResource($lead),
            'Lead updated successfully'
        );
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorize('delete', $lead);

        DB::transaction(function () use ($lead) {
            $this->leadRepository->delete($lead->id);
        });

        return ApiResponse::success(
            null,
            'Lead deleted successfully'
        );
    }

    /**
     * Convert lead to customer.
     */
    public function convert(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('convert', $lead);

        $customerData = $request->validate([
            'customer_type' => ['required', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
        ]);

        $customer = DB::transaction(function () use ($lead, $customerData) {
            return $this->leadConversionService->convertToCustomer($lead, $customerData);
        });

        $customer->load(['organization', 'primaryContact']);

        return ApiResponse::success(
            new CustomerResource($customer),
            'Lead converted to customer successfully'
        );
    }

    /**
     * Assign lead to a user.
     */
    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('assign', $lead);

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'exists:users,id',
            ],
        ]);

        $lead = DB::transaction(function () use ($lead, $validated) {
            return $this->leadRepository->update($lead->id, $validated);
        });

        return ApiResponse::success(
            new LeadResource($lead),
            'Lead assigned successfully'
        );
    }
}

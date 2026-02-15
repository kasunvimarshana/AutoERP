<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\BaseController;
use App\Http\Resources\CRM\LeadResource;
use App\Http\Resources\CRM\CustomerResource;
use App\Modules\CRM\Services\CRMService;
use App\Http\Requests\CRM\StoreLeadRequest;
use App\Http\Requests\CRM\UpdateLeadRequest;
use App\Http\Requests\CRM\ConvertLeadRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeadController extends BaseController
{
    protected CRMService $crmService;

    public function __construct(CRMService $crmService)
    {
        $this->crmService = $crmService;
    }

    /**
     * Display a listing of leads.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            
            // Access through repository interface
            $leadRepository = app(\App\Modules\CRM\Repositories\LeadRepositoryInterface::class);
            $leads = $leadRepository->paginate($perPage);

            return $this->paginatedResponse(
                LeadResource::collection($leads),
                'Leads retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve leads: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created lead.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['tenant_id'] = auth()->user()->tenant_id;
            $validated['created_by'] = auth()->id();

            $lead = $this->crmService->createLead($validated);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create lead: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified lead.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $leadRepository = app(\App\Modules\CRM\Repositories\LeadRepositoryInterface::class);
            $lead = $leadRepository->findById($id);

            if (!$lead) {
                return $this->errorResponse('Lead not found', [], 404);
            }

            $this->authorize('view', $lead);

            return $this->successResponse(
                new LeadResource($lead->load(['assignedUser', 'convertedCustomer'])),
                'Lead retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve lead: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified lead.
     */
    public function update(UpdateLeadRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['updated_by'] = auth()->id();

            $lead = $this->crmService->updateLead($id, $validated);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update lead: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $leadRepository = app(\App\Modules\CRM\Repositories\LeadRepositoryInterface::class);
            $lead = $leadRepository->findById($id);

            if (!$lead) {
                return $this->errorResponse('Lead not found', [], 404);
            }

            $this->authorize('delete', $lead);

            $leadRepository->delete($id);

            return $this->successResponse(null, 'Lead deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete lead: ' . $e->getMessage());
        }
    }

    /**
     * Convert lead to customer.
     */
    public function convert(ConvertLeadRequest $request, int $id): JsonResponse
    {
        try {
            $leadRepository = app(\App\Modules\CRM\Repositories\LeadRepositoryInterface::class);
            $lead = $leadRepository->findById($id);

            if (!$lead) {
                return $this->errorResponse('Lead not found', [], 404);
            }

            $this->authorize('update', $lead);

            $validated = $request->validated();
            $customer = $this->crmService->convertLead($id, $validated);

            return $this->successResponse(
                new CustomerResource($customer),
                'Lead converted to customer successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to convert lead: ' . $e->getMessage());
        }
    }

    /**
     * Search leads.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('q', '');
            $filters = $request->except(['q', 'page', 'per_page']);

            $leads = $this->crmService->searchLeads($query, $filters);

            return $this->successResponse(
                LeadResource::collection($leads),
                'Search results retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Get lead statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->crmService->getLeadStatistics();

            return $this->successResponse($stats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics: ' . $e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sales\Http\Requests\StoreLeadRequest;
use Modules\Sales\Http\Requests\UpdateLeadRequest;
use Modules\Sales\Http\Resources\LeadResource;
use Modules\Sales\Services\LeadService;

/**
 * Lead API Controller
 *
 * Handles API requests for lead management.
 */
class LeadController extends Controller
{
    /**
     * LeadController constructor.
     */
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Display a listing of leads.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $leads = $this->leadService->getPaginated($perPage);

        return LeadResource::collection($leads)
            ->response();
    }

    /**
     * Store a newly created lead.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->create($request->validated());

        return (new LeadResource($lead))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified lead.
     */
    public function show(int $id): JsonResponse
    {
        $lead = $this->leadService->findById($id);

        if (! $lead) {
            return response()->json([
                'message' => 'Lead not found',
            ], 404);
        }

        return (new LeadResource($lead))->response();
    }

    /**
     * Update the specified lead.
     */
    public function update(UpdateLeadRequest $request, int $id): JsonResponse
    {
        $lead = $this->leadService->update($id, $request->validated());

        return (new LeadResource($lead))->response();
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->leadService->delete($id);

        if (! $deleted) {
            return response()->json([
                'message' => 'Lead not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Lead deleted successfully',
        ], 200);
    }

    /**
     * Search leads.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $leads = $this->leadService->search($query);

        return LeadResource::collection($leads)
            ->response();
    }

    /**
     * Convert lead to customer.
     */
    public function convert(Request $request, int $id): JsonResponse
    {
        try {
            $customerData = $request->input('customer_data');
            $customer = $this->leadService->convertToCustomer($id, $customerData);

            return response()->json([
                'message' => 'Lead converted to customer successfully',
                'customer' => $customer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

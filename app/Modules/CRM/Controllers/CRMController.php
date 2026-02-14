<?php

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Services\CampaignService;
use App\Modules\CRM\Services\LeadService;
use App\Modules\CRM\Services\OpportunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRM Controller
 *
 * @OA\Tag(name="CRM", description="CRM module endpoints")
 */
class CRMController extends Controller
{
    protected LeadService $leadService;

    protected OpportunityService $opportunityService;

    protected CampaignService $campaignService;

    public function __construct(
        LeadService $leadService,
        OpportunityService $opportunityService,
        CampaignService $campaignService
    ) {
        $this->leadService = $leadService;
        $this->opportunityService = $opportunityService;
        $this->campaignService = $campaignService;
    }

    public function leads(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $leads = $this->leadService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $leads,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:new,contacted,qualified,lost',
            'notes' => 'nullable|string',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $lead = $this->leadService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Lead created successfully',
                'data' => $lead,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showLead(int $id): JsonResponse
    {
        try {
            $lead = $this->leadService->find($id);

            if (! $lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $lead,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateLead(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:new,contacted,qualified,lost',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->leadService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Lead updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyLead(int $id): JsonResponse
    {
        try {
            $result = $this->leadService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Lead deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lead',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function opportunities(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $opportunities = $this->opportunityService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $opportunities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeOpportunity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_id' => 'required|exists:customers,id',
            'value' => 'required|numeric|min:0',
            'stage' => 'nullable|string|in:prospecting,qualification,proposal,negotiation,closed_won,closed_lost',
            'probability' => 'nullable|numeric|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $opportunity = $this->opportunityService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Opportunity created successfully',
                'data' => $opportunity,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create opportunity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showOpportunity(int $id): JsonResponse
    {
        try {
            $opportunity = $this->opportunityService->find($id);

            if (! $opportunity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Opportunity not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $opportunity,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateOpportunity(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'customer_id' => 'sometimes|exists:customers,id',
            'value' => 'sometimes|numeric|min:0',
            'stage' => 'nullable|string|in:prospecting,qualification,proposal,negotiation,closed_won,closed_lost',
            'probability' => 'nullable|numeric|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->opportunityService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Opportunity updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update opportunity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyOpportunity(int $id): JsonResponse
    {
        try {
            $result = $this->opportunityService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Opportunity deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete opportunity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function campaigns(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $campaigns = $this->campaignService->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $campaigns,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:planning,active,paused,completed',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $campaign = $this->campaignService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'data' => $campaign,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showCampaign(int $id): JsonResponse
    {
        try {
            $campaign = $this->campaignService->find($id);

            if (! $campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $campaign,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCampaign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:planning,active,paused,completed',
            'budget' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        try {
            $result = $this->campaignService->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyCampaign(int $id): JsonResponse
    {
        try {
            $result = $this->campaignService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

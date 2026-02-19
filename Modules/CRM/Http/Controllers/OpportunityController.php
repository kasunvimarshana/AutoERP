<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\CRM\Http\Requests\StoreOpportunityRequest;
use Modules\CRM\Http\Requests\UpdateOpportunityRequest;
use Modules\CRM\Http\Resources\OpportunityResource;
use Modules\CRM\Models\Opportunity;
use Modules\CRM\Repositories\OpportunityRepository;
use Modules\CRM\Services\OpportunityService;

class OpportunityController extends Controller
{
    public function __construct(
        private OpportunityRepository $opportunityRepository,
        private OpportunityService $opportunityService
    ) {}

    /**
     * Display a listing of opportunities.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        $query = Opportunity::query()
            ->with(['customer', 'organization']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('stage')) {
            $query->where('stage', $request->stage);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->assigned_to_user_id);
        }

        if ($request->has('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->has('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('opportunity_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $opportunities = $query->paginate($perPage);

        return ApiResponse::paginated(
            $opportunities->setCollection(
                $opportunities->getCollection()->map(fn ($opp) => new OpportunityResource($opp))
            ),
            'Opportunities retrieved successfully'
        );
    }

    /**
     * Store a newly created opportunity.
     */
    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $this->authorize('create', Opportunity::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;

        $opportunity = $this->opportunityService->createOpportunity($data);
        $opportunity->load(['customer', 'organization']);

        return ApiResponse::created(
            new OpportunityResource($opportunity),
            'Opportunity created successfully'
        );
    }

    /**
     * Display the specified opportunity.
     */
    public function show(Opportunity $opportunity): JsonResponse
    {
        $this->authorize('view', $opportunity);

        $opportunity->load(['customer', 'organization']);

        return ApiResponse::success(
            new OpportunityResource($opportunity),
            'Opportunity retrieved successfully'
        );
    }

    /**
     * Update the specified opportunity.
     */
    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $data = $request->validated();

        // Update probability if stage changed but probability not provided
        if (! empty($data['stage']) && empty($data['probability'])) {
            $stage = OpportunityStage::from($data['stage']);
            $data['probability'] = $stage->probability();
        }

        $opportunity = DB::transaction(function () use ($opportunity, $data) {
            return $this->opportunityRepository->update($opportunity->id, $data);
        });

        $opportunity->load(['customer', 'organization']);

        return ApiResponse::success(
            new OpportunityResource($opportunity),
            'Opportunity updated successfully'
        );
    }

    /**
     * Remove the specified opportunity.
     */
    public function destroy(Opportunity $opportunity): JsonResponse
    {
        $this->authorize('delete', $opportunity);

        DB::transaction(function () use ($opportunity) {
            $this->opportunityRepository->delete($opportunity->id);
        });

        return ApiResponse::success(
            null,
            'Opportunity deleted successfully'
        );
    }

    /**
     * Get pipeline statistics.
     */
    public function pipelineStats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Opportunity::class);

        $filters = [];
        if ($request->has('assigned_to_user_id')) {
            $filters['assigned_to_user_id'] = $request->assigned_to_user_id;
        }
        if ($request->has('organization_id')) {
            $filters['organization_id'] = $request->organization_id;
        }

        $stats = $this->opportunityService->getPipelineStats($filters);

        return ApiResponse::success(
            $stats,
            'Pipeline statistics retrieved successfully'
        );
    }

    /**
     * Advance opportunity to next stage.
     */
    public function advance(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $opportunity = DB::transaction(function () use ($opportunity, $validated) {
            return $this->opportunityService->advanceStage($opportunity, $validated['notes'] ?? null);
        });

        return ApiResponse::success(
            new OpportunityResource($opportunity),
            'Opportunity advanced to next stage'
        );
    }

    /**
     * Mark opportunity as won.
     */
    public function win(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $validated = $request->validate([
            'actual_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $opportunity = DB::transaction(function () use ($opportunity, $validated) {
            return $this->opportunityService->markAsWon(
                $opportunity,
                $validated['actual_close_date'] ?? now(),
                $validated['notes'] ?? null
            );
        });

        return ApiResponse::success(
            new OpportunityResource($opportunity),
            'Opportunity marked as won'
        );
    }

    /**
     * Mark opportunity as lost.
     */
    public function lose(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $validated = $request->validate([
            'actual_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $opportunity = DB::transaction(function () use ($opportunity, $validated) {
            return $this->opportunityService->markAsLost(
                $opportunity,
                $validated['actual_close_date'] ?? now(),
                $validated['notes'] ?? null
            );
        });

        return ApiResponse::success(
            new OpportunityResource($opportunity),
            'Opportunity marked as lost'
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\CRM\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\CRM\Application\DTOs\CreateLeadDTO;
use Modules\CRM\Application\DTOs\CreateOpportunityDTO;
use Modules\CRM\Application\Services\CRMService;

/**
 * CRM controller.
 *
 * Input validation and response formatting ONLY.
 * All business logic is delegated to CRMService.
 *
 * @OA\Tag(name="CRM", description="CRM lead and opportunity management endpoints")
 */
class CRMController extends Controller
{
    public function __construct(private readonly CRMService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/crm/leads",
     *     tags={"CRM"},
     *     summary="List leads",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"new","contacted","qualified","unqualified","converted"})),
     *     @OA\Parameter(name="assigned_to", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of leads"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listLeads(Request $request): JsonResponse
    {
        $leads = $this->service->listLeads($request->only(['status', 'assigned_to']));

        return ApiResponse::success($leads, 'Leads retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/crm/leads/{id}",
     *     tags={"CRM"},
     *     summary="Show a single lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lead details"),
     *     @OA\Response(response=404, description="Lead not found")
     * )
     */
    public function showLead(int $id): JsonResponse
    {
        $lead = $this->service->showLead($id);

        return ApiResponse::success($lead, 'Lead retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/crm/leads",
     *     tags={"CRM"},
     *     summary="Create a new lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name"},
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string", nullable=true),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="company", type="string", nullable=true),
     *             @OA\Property(property="source", type="string", nullable=true, enum={"website","referral","campaign","social","direct"}),
     *             @OA\Property(property="assigned_to", type="integer", nullable=true),
     *             @OA\Property(property="campaign_id", type="integer", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Lead created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'email'       => ['nullable', 'string', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'company'     => ['nullable', 'string', 'max:255'],
            'source'      => ['nullable', 'string', 'in:website,referral,campaign,social,direct'],
            'assigned_to' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
            'notes'       => ['nullable', 'string'],
        ]);

        $dto  = CreateLeadDTO::fromArray($validated);
        $lead = $this->service->createLead($dto);

        return ApiResponse::created($lead, 'Lead created.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/crm/leads/{id}/convert",
     *     tags={"CRM"},
     *     summary="Convert a lead to an opportunity",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pipeline_stage_id","title","expected_revenue","probability"},
     *             @OA\Property(property="pipeline_stage_id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="expected_revenue", type="string", example="10000.0000"),
     *             @OA\Property(property="close_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="assigned_to", type="integer", nullable=true),
     *             @OA\Property(property="probability", type="string", example="50.00"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Opportunity created"),
     *     @OA\Response(response=404, description="Lead not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function convertLeadToOpportunity(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'pipeline_stage_id' => ['required', 'integer'],
            'title'             => ['required', 'string', 'max:255'],
            'expected_revenue'  => ['required', 'numeric', 'min:0'],
            'close_date'        => ['nullable', 'date'],
            'assigned_to'       => ['nullable', 'integer'],
            'probability'       => ['required', 'numeric', 'min:0', 'max:100'],
            'notes'             => ['nullable', 'string'],
        ]);

        $dto         = CreateOpportunityDTO::fromArray($validated);
        $opportunity = $this->service->convertLeadToOpportunity($id, $dto);

        return ApiResponse::created($opportunity, 'Lead converted to opportunity.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/crm/opportunities",
     *     tags={"CRM"},
     *     summary="List opportunities",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assigned_to", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of opportunities"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listOpportunities(Request $request): JsonResponse
    {
        $opportunities = $this->service->listOpportunities($request->query());

        return ApiResponse::success($opportunities, 'Opportunities retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/crm/opportunities/{id}/stage",
     *     tags={"CRM"},
     *     summary="Update the pipeline stage of an opportunity",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"pipeline_stage_id"},
     *             @OA\Property(property="pipeline_stage_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Stage updated"),
     *     @OA\Response(response=404, description="Opportunity not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateOpportunityStage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'pipeline_stage_id' => ['required', 'integer'],
        ]);

        $opportunity = $this->service->updateOpportunityStage($id, (int) $validated['pipeline_stage_id']);

        return ApiResponse::success($opportunity, 'Opportunity stage updated.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/crm/opportunities/{id}/close-won",
     *     tags={"CRM"},
     *     summary="Close an opportunity as won",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Opportunity closed as won"),
     *     @OA\Response(response=404, description="Opportunity not found")
     * )
     */
    public function closeWon(int $id): JsonResponse
    {
        $opportunity = $this->service->closeWon($id);

        return ApiResponse::success($opportunity, 'Opportunity closed as won.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/crm/opportunities/{id}/close-lost",
     *     tags={"CRM"},
     *     summary="Close an opportunity as lost",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Opportunity closed as lost"),
     *     @OA\Response(response=404, description="Opportunity not found")
     * )
     */
    public function closeLost(int $id): JsonResponse
    {
        $opportunity = $this->service->closeLost($id);

        return ApiResponse::success($opportunity, 'Opportunity closed as lost.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/crm/opportunities/{id}",
     *     tags={"CRM"},
     *     summary="Show a single opportunity",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Opportunity details"),
     *     @OA\Response(response=404, description="Opportunity not found")
     * )
     */
    public function showOpportunity(int $id): JsonResponse
    {
        $opportunity = $this->service->showOpportunity($id);

        return ApiResponse::success($opportunity, 'Opportunity retrieved.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/crm/leads/{id}",
     *     tags={"CRM"},
     *     summary="Delete a lead",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lead deleted"),
     *     @OA\Response(response=404, description="Lead not found")
     * )
     */
    public function deleteLead(int $id): JsonResponse
    {
        $this->service->deleteLead($id);

        return ApiResponse::success(null, 'Lead deleted.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/crm/customers",
     *     tags={"CRM"},
     *     summary="List customers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of customers"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listCustomers(): JsonResponse
    {
        $customers = $this->service->listCustomers();

        return ApiResponse::success($customers, 'Customers retrieved.');
    }
}

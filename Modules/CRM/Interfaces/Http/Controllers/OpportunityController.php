<?php

declare(strict_types=1);

namespace Modules\CRM\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Domain\Contracts\OpportunityRepositoryInterface;
use Modules\CRM\Domain\Entities\Opportunity;

/**
 * OpportunityController: manages CRM opportunities and the pipeline board.
 */
class OpportunityController extends Controller
{
    private const STAGES = [
        'prospecting',
        'qualification',
        'proposal',
        'negotiation',
        'closed_won',
        'closed_lost',
    ];

    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * GET /api/v1/opportunities
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $stage    = $request->query('stage') ?: null;
        $page     = (int) ($request->query('page', 1));
        $perPage  = min((int) ($request->query('per_page', 25)), 100);

        $items = $this->opportunities->findAll($tenantId, $stage, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Opportunities retrieved successfully.',
            'data'    => array_map(fn ($o) => $this->format($o), $items),
            'errors'  => null,
        ]);
    }

    /**
     * GET /api/v1/crm/pipeline
     * Returns all open opportunities grouped by pipeline stage with stage totals.
     */
    public function pipeline(Request $request): JsonResponse
    {
        $tenantId      = (int) $request->attributes->get('tenant_id');
        $opportunities = $this->opportunities->findOpen($tenantId);

        $grouped = [];
        foreach ($opportunities as $opp) {
            $grouped[$opp->getStage()][] = $opp;
        }

        $board = [];
        foreach (self::STAGES as $stage) {
            if (in_array($stage, ['closed_won', 'closed_lost'], true)) {
                continue;
            }
            $stageItems = $grouped[$stage] ?? [];
            $total      = array_reduce(
                $stageItems,
                fn (string $carry, Opportunity $o): string => bcadd($carry, $o->getValue(), 4),
                '0.0000'
            );
            $board[] = [
                'stage'       => $stage,
                'count'       => count($stageItems),
                'total_value' => $total,
                'items'       => array_map(fn ($o) => $this->format($o), $stageItems),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Pipeline board retrieved successfully.',
            'data'    => $board,
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/opportunities
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'lead_id'             => 'nullable|integer|exists:leads,id',
            'contact_id'          => 'nullable|integer|exists:contacts,id',
            'stage'               => 'required|string|in:' . implode(',', self::STAGES),
            'value'               => 'nullable|numeric|min:0',
            'probability'         => 'nullable|numeric|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'assigned_to'         => 'nullable|integer|exists:users,id',
            'notes'               => 'nullable|string',
        ]);

        $opportunity = new Opportunity(
            id: 0,
            tenantId: (int) $request->attributes->get('tenant_id'),
            leadId: isset($validated['lead_id']) ? (int) $validated['lead_id'] : null,
            contactId: isset($validated['contact_id']) ? (int) $validated['contact_id'] : null,
            title: $validated['title'],
            stage: $validated['stage'],
            value: bcadd((string) ($validated['value'] ?? '0'), '0', 4),
            probability: isset($validated['probability'])
                ? bcdiv((string) $validated['probability'], '100', 4)
                : '0.0000',
            expectedCloseDate: $validated['expected_close_date'] ?? null,
            assignedTo: isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null,
            notes: $validated['notes'] ?? null,
        );

        $saved = $this->opportunities->save($opportunity);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity created successfully.',
            'data'    => $this->format($saved),
            'errors'  => null,
        ], 201);
    }

    /**
     * GET /api/v1/opportunities/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId    = (int) $request->attributes->get('tenant_id');
        $opportunity = $this->opportunities->findById($id, $tenantId);

        if ($opportunity === null) {
            return response()->json([
                'success' => false,
                'message' => 'Opportunity not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Opportunity retrieved successfully.',
            'data'    => $this->format($opportunity),
            'errors'  => null,
        ]);
    }

    /**
     * PUT /api/v1/opportunities/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId    = (int) $request->attributes->get('tenant_id');
        $opportunity = $this->opportunities->findById($id, $tenantId);

        if ($opportunity === null) {
            return response()->json([
                'success' => false,
                'message' => 'Opportunity not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validated = $request->validate([
            'title'               => 'sometimes|string|max:255',
            'stage'               => 'sometimes|string|in:' . implode(',', self::STAGES),
            'value'               => 'nullable|numeric|min:0',
            'probability'         => 'nullable|numeric|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'assigned_to'         => 'nullable|integer|exists:users,id',
            'notes'               => 'nullable|string',
        ]);

        $updated = new Opportunity(
            id: $opportunity->getId(),
            tenantId: $opportunity->getTenantId(),
            leadId: $opportunity->getLeadId(),
            contactId: $opportunity->getContactId(),
            title: $validated['title'] ?? $opportunity->getTitle(),
            stage: $validated['stage'] ?? $opportunity->getStage(),
            value: isset($validated['value']) ? bcadd((string) $validated['value'], '0', 4) : $opportunity->getValue(),
            probability: isset($validated['probability'])
                ? bcdiv((string) $validated['probability'], '100', 4)
                : $opportunity->getProbability(),
            expectedCloseDate: array_key_exists('expected_close_date', $validated)
                ? $validated['expected_close_date']
                : $opportunity->getExpectedCloseDate(),
            assignedTo: array_key_exists('assigned_to', $validated)
                ? (isset($validated['assigned_to']) ? (int) $validated['assigned_to'] : null)
                : $opportunity->getAssignedTo(),
            notes: array_key_exists('notes', $validated) ? $validated['notes'] : $opportunity->getNotes(),
        );

        $saved = $this->opportunities->save($updated);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity updated successfully.',
            'data'    => $this->format($saved),
            'errors'  => null,
        ]);
    }

    /**
     * DELETE /api/v1/opportunities/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        if ($this->opportunities->findById($id, $tenantId) === null) {
            return response()->json([
                'success' => false,
                'message' => 'Opportunity not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $this->opportunities->delete($id, $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity deleted successfully.',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    private function format(Opportunity $o): array
    {
        return [
            'id'                  => $o->getId(),
            'tenant_id'           => $o->getTenantId(),
            'lead_id'             => $o->getLeadId(),
            'contact_id'          => $o->getContactId(),
            'title'               => $o->getTitle(),
            'stage'               => $o->getStage(),
            'value'               => $o->getValue(),
            'probability'         => $o->getProbability(),
            'expected_close_date' => $o->getExpectedCloseDate(),
            'assigned_to'         => $o->getAssignedTo(),
            'notes'               => $o->getNotes(),
        ];
    }
}


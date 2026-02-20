<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WorkflowEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $workflowEngine
    ) {}

    /**
     * List all workflow definitions for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $perPage = min((int) $request->query('per_page', 15), 100);
        $filters = $request->only(['entity_type', 'is_active']);

        return response()->json(
            $this->workflowEngine->paginate($tenantId, $filters, $perPage)
        );
    }

    /**
     * Create a new workflow definition (with optional inline states and transitions).
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.manage'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'entity_type' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
            'states' => 'nullable|array',
            'states.*.name' => 'required|string|max:100',
            'states.*.label' => 'nullable|string|max:255',
            'states.*.is_initial' => 'boolean',
            'states.*.is_final' => 'boolean',
            'states.*.metadata' => 'nullable|array',
            'transitions' => 'nullable|array',
            'transitions.*.name' => 'required|string|max:100',
            'transitions.*.from_state_name' => 'required_without:transitions.*.from_state_id|string',
            'transitions.*.to_state_name' => 'required_without:transitions.*.to_state_id|string',
            'transitions.*.from_state_id' => 'required_without:transitions.*.from_state_name|uuid',
            'transitions.*.to_state_id' => 'required_without:transitions.*.to_state_name|uuid',
            'transitions.*.required_permission' => 'nullable|string|max:100',
            'transitions.*.conditions' => 'nullable|array',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        return response()->json(
            $this->workflowEngine->createDefinition($data),
            201
        );
    }

    /**
     * Update a workflow definition.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.manage'), 403);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        return response()->json(
            $this->workflowEngine->updateDefinition($id, $data)
        );
    }

    /**
     * Start a workflow instance for a specific entity.
     */
    public function startInstance(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.manage'), 403);

        $data = $request->validate([
            'workflow_definition_id' => 'required|uuid',
            'entity_type' => 'required|string|max:100',
            'entity_id' => 'required|uuid',
            'context' => 'nullable|array',
        ]);

        $tenantId = $request->user()->tenant_id;

        return response()->json(
            $this->workflowEngine->startInstance(
                definitionId: $data['workflow_definition_id'],
                entityType: $data['entity_type'],
                entityId: $data['entity_id'],
                tenantId: $tenantId,
                userId: $request->user()->id,
                context: $data['context'] ?? [],
            ),
            201
        );
    }

    /**
     * Apply a transition to an active workflow instance.
     */
    public function applyTransition(Request $request, string $instanceId): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.manage'), 403);

        $data = $request->validate([
            'transition_id' => 'required|uuid',
            'comment' => 'nullable|string|max:1000',
        ]);

        $tenantId = $request->user()->tenant_id;

        return response()->json(
            $this->workflowEngine->transition(
                instanceId: $instanceId,
                transitionId: $data['transition_id'],
                tenantId: $tenantId,
                userId: $request->user()->id,
                comment: $data['comment'] ?? null,
            )
        );
    }

    /**
     * Cancel a running workflow instance.
     */
    public function cancelInstance(Request $request, string $instanceId): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.manage'), 403);

        $tenantId = $request->user()->tenant_id;

        return response()->json(
            $this->workflowEngine->cancelInstance(
                instanceId: $instanceId,
                tenantId: $tenantId,
                userId: $request->user()->id,
            )
        );
    }

    /**
     * Get the active workflow instance for a given entity.
     */
    public function getEntityInstance(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('workflow.view'), 403);

        $data = $request->validate([
            'entity_type' => 'required|string|max:100',
            'entity_id' => 'required|uuid',
        ]);

        $tenantId = $request->user()->tenant_id;

        $instance = $this->workflowEngine->getInstance(
            entityType: $data['entity_type'],
            entityId: $data['entity_id'],
            tenantId: $tenantId,
        );

        if (! $instance) {
            return response()->json(['message' => 'No workflow instance found.'], 404);
        }

        return response()->json($instance);
    }
}

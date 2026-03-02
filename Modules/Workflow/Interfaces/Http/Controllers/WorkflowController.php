<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Workflow\Application\DTOs\CreateWorkflowDTO;
use Modules\Workflow\Application\DTOs\CreateWorkflowInstanceDTO;
use Modules\Workflow\Application\Services\WorkflowService;

/**
 * Workflow controller.
 *
 * Input validation, authorization checks, and response formatting ONLY.
 * No business logic — all delegated to WorkflowService.
 *
 * @OA\Tag(name="Workflow", description="Workflow definition management endpoints")
 */
class WorkflowController extends Controller
{
    public function __construct(private readonly WorkflowService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/workflows",
     *     tags={"Workflow"},
     *     summary="List workflow definitions (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of workflow definitions"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->list($perPage);

        return ApiResponse::paginated($paginator, 'Workflow definitions retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/workflows",
     *     tags={"Workflow"},
     *     summary="Create a new workflow definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","entity_type"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="entity_type", type="string", example="sales_order"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Workflow definition created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $dto = CreateWorkflowDTO::fromArray($validated);
        $workflow = $this->service->create($dto);

        return ApiResponse::created($workflow, 'Workflow definition created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/workflows/{id}",
     *     tags={"Workflow"},
     *     summary="Get a single workflow definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Workflow definition data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $workflow = $this->service->show($id);

        return ApiResponse::success($workflow, 'Workflow definition retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/workflows/{id}",
     *     tags={"Workflow"},
     *     summary="Update a workflow definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="entity_type", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Workflow definition updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'entity_type' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $workflow = $this->service->update($id, $validated);

        return ApiResponse::success($workflow, 'Workflow definition updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/workflows/{id}",
     *     tags={"Workflow"},
     *     summary="Delete a workflow definition",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/workflow-instances",
     *     tags={"Workflow"},
     *     summary="Create a new workflow instance for an entity",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"workflow_definition_id","entity_type","entity_id"},
     *             @OA\Property(property="workflow_definition_id", type="integer"),
     *             @OA\Property(property="entity_type", type="string", example="sales_order"),
     *             @OA\Property(property="entity_id", type="integer"),
     *             @OA\Property(property="initial_state_id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Workflow instance created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createInstance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workflow_definition_id' => ['required', 'integer', 'min:1'],
            'entity_type'            => ['required', 'string', 'max:255'],
            'entity_id'              => ['required', 'integer', 'min:1'],
            'initial_state_id'       => ['nullable', 'integer', 'min:1'],
        ]);

        $dto      = CreateWorkflowInstanceDTO::fromArray($validated);
        $instance = $this->service->createInstance($dto);

        return ApiResponse::created($instance, 'Workflow instance created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/workflow-instances",
     *     tags={"Workflow"},
     *     summary="List workflow instances by entity type",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="entity_type", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="List of workflow instances"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function listInstances(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', 'string', 'max:255'],
        ]);

        $instances = $this->service->listInstances($validated['entity_type']);

        return ApiResponse::success($instances, 'Workflow instances retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/workflow-instances/{id}/transition",
     *     tags={"Workflow"},
     *     summary="Apply a state transition to a workflow instance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to_state_id"},
     *             @OA\Property(property="to_state_id", type="integer"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transition applied"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function applyTransition(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'to_state_id' => ['required', 'integer', 'min:1'],
            'notes'       => ['nullable', 'string'],
        ]);

        $instance = $this->service->applyTransition($id, $validated['to_state_id'], $validated['notes'] ?? null);

        return ApiResponse::success($instance, 'Transition applied.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/workflow-instances/{id}",
     *     tags={"Workflow"},
     *     summary="Show a single workflow instance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Workflow instance data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showInstance(int $id): JsonResponse
    {
        $instance = $this->service->showInstance($id);

        return ApiResponse::success($instance, 'Workflow instance retrieved.');
    }
}

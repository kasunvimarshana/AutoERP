<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Workflow\Http\Requests\ExecuteWorkflowRequest;
use Modules\Workflow\Http\Requests\StoreWorkflowRequest;
use Modules\Workflow\Http\Requests\UpdateWorkflowRequest;
use Modules\Workflow\Models\Workflow;
use Modules\Workflow\Repositories\WorkflowRepository;
use Modules\Workflow\Resources\WorkflowResource;
use Modules\Workflow\Services\WorkflowBuilder;
use Modules\Workflow\Services\WorkflowEngine;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowRepository $workflowRepository,
        private WorkflowBuilder $builder,
        private WorkflowEngine $engine
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Workflow::class);

        $filters = $request->only(['status', 'entity_type', 'is_template', 'search']);
        $workflows = $this->workflowRepository->paginate($filters, $request->get('per_page', 15));

        return ApiResponse::paginated(
            $workflows->setCollection(
                $workflows->getCollection()->map(fn ($workflow) => new WorkflowResource($workflow))
            ),
            'Workflows retrieved successfully'
        );
    }

    public function show(Workflow $workflow): JsonResponse
    {
        $this->authorize('view', $workflow);

        $workflow->load(['steps.conditions', 'creator', 'updater']);

        return ApiResponse::success(
            new WorkflowResource($workflow),
            'Workflow retrieved successfully'
        );
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $workflow = $this->builder->create($request->validated());

        return ApiResponse::created(
            new WorkflowResource($workflow),
            'Workflow created successfully'
        );
    }

    public function update(UpdateWorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $workflow = $this->builder->update($workflow, $request->validated());

        return ApiResponse::success(
            new WorkflowResource($workflow),
            'Workflow updated successfully'
        );
    }

    public function destroy(Workflow $workflow): JsonResponse
    {
        $this->authorize('delete', $workflow);

        $this->workflowRepository->deleteWorkflow($workflow);

        return ApiResponse::success(null, 'Workflow deleted successfully');
    }

    public function execute(ExecuteWorkflowRequest $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('execute', $workflow);

        $instance = $this->engine->start($workflow, $request->validated()['context']);

        return ApiResponse::created(
            ['instance_id' => $instance->id],
            'Workflow execution started successfully'
        );
    }

    public function activate(Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $this->builder->activate($workflow);

        return ApiResponse::success(
            new WorkflowResource($workflow->fresh()),
            'Workflow activated successfully'
        );
    }

    public function deactivate(Workflow $workflow): JsonResponse
    {
        $this->authorize('update', $workflow);

        $workflow->deactivate();

        return ApiResponse::success(
            new WorkflowResource($workflow->fresh()),
            'Workflow deactivated successfully'
        );
    }

    public function duplicate(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorize('create', Workflow::class);

        $newWorkflow = $this->workflowRepository->duplicate($workflow, [
            'name' => $request->input('name', $workflow->name.' (Copy)'),
        ]);

        return ApiResponse::created(
            new WorkflowResource($newWorkflow),
            'Workflow duplicated successfully'
        );
    }
}

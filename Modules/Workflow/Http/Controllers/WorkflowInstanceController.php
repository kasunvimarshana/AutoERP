<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Repositories\WorkflowInstanceRepository;
use Modules\Workflow\Resources\WorkflowInstanceResource;
use Modules\Workflow\Services\WorkflowEngine;

class WorkflowInstanceController extends Controller
{
    public function __construct(
        private WorkflowInstanceRepository $instanceRepository,
        private WorkflowEngine $engine
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkflowInstance::class);

        $filters = $request->only(['workflow_id', 'status', 'entity_type', 'entity_id', 'started_by']);
        $instances = $this->instanceRepository->paginate($filters, $request->get('per_page', 15));

        return ApiResponse::paginated(
            $instances->setCollection(
                $instances->getCollection()->map(fn ($instance) => new WorkflowInstanceResource($instance))
            ),
            'Workflow instances retrieved successfully'
        );
    }

    public function show(WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('view', $workflowInstance);

        $workflowInstance->load(['workflow.steps', 'instanceSteps.step', 'approvals', 'starter']);

        return ApiResponse::success(
            new WorkflowInstanceResource($workflowInstance),
            'Workflow instance retrieved successfully'
        );
    }

    public function cancel(WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('cancel', $workflowInstance);

        $workflowInstance->cancel();

        return ApiResponse::success(
            new WorkflowInstanceResource($workflowInstance->fresh()),
            'Workflow instance cancelled successfully'
        );
    }

    public function resume(Request $request, WorkflowInstance $workflowInstance): JsonResponse
    {
        $this->authorize('resume', $workflowInstance);

        $this->engine->resume($workflowInstance, $request->input('data', []));

        return ApiResponse::success(
            new WorkflowInstanceResource($workflowInstance->fresh()),
            'Workflow instance resumed successfully'
        );
    }
}

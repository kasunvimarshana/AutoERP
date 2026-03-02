<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Workflow\Application\Commands\AdvanceWorkflowInstanceCommand;
use Modules\Workflow\Application\Commands\CancelWorkflowInstanceCommand;
use Modules\Workflow\Application\Commands\DeleteWorkflowInstanceCommand;
use Modules\Workflow\Application\Commands\StartWorkflowInstanceCommand;
use Modules\Workflow\Application\Services\WorkflowInstanceService;
use Modules\Workflow\Interfaces\Http\Requests\AdvanceWorkflowInstanceRequest;
use Modules\Workflow\Interfaces\Http\Requests\CancelWorkflowInstanceRequest;
use Modules\Workflow\Interfaces\Http\Requests\StartWorkflowInstanceRequest;
use Modules\Workflow\Interfaces\Http\Resources\WorkflowInstanceLogResource;
use Modules\Workflow\Interfaces\Http\Resources\WorkflowInstanceResource;

class WorkflowInstanceController extends BaseController
{
    public function __construct(
        private readonly WorkflowInstanceService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAllInstances($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($inst) => (new WorkflowInstanceResource($inst))->resolve(),
                $result['items']
            ),
            message: 'Workflow instances retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(StartWorkflowInstanceRequest $request): JsonResponse
    {
        try {
            $instance = $this->service->startInstance(new StartWorkflowInstanceCommand(
                tenantId: $request->validated('tenant_id'),
                workflowDefinitionId: $request->validated('workflow_definition_id'),
                entityType: $request->validated('entity_type'),
                entityId: $request->validated('entity_id'),
                startedByUserId: $request->validated('started_by_user_id'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new WorkflowInstanceResource($instance))->resolve(),
            message: 'Workflow instance started successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $instance = $this->service->findInstanceById($id, $tenantId);

        if ($instance === null) {
            return $this->error('Workflow instance not found', status: 404);
        }

        return $this->success(
            data: (new WorkflowInstanceResource($instance))->resolve(),
            message: 'Workflow instance retrieved successfully',
        );
    }

    public function advance(AdvanceWorkflowInstanceRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $instance = $this->service->advanceInstance(new AdvanceWorkflowInstanceCommand(
                tenantId: $tenantId,
                instanceId: $id,
                transitionId: $request->validated('transition_id'),
                actorUserId: $request->validated('actor_user_id'),
                comment: $request->validated('comment'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new WorkflowInstanceResource($instance))->resolve(),
            message: 'Workflow instance advanced successfully',
        );
    }

    public function cancel(CancelWorkflowInstanceRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $instance = $this->service->cancelInstance(new CancelWorkflowInstanceCommand(
                tenantId: $tenantId,
                instanceId: $id,
                actorUserId: $request->validated('actor_user_id'),
                comment: $request->validated('comment'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new WorkflowInstanceResource($instance))->resolve(),
            message: 'Workflow instance cancelled successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteInstance(new DeleteWorkflowInstanceCommand($id, $tenantId));

            return $this->success(message: 'Workflow instance deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function logs(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $logs = $this->service->findInstanceLogs($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($log) => (new WorkflowInstanceLogResource($log))->resolve(),
                $logs
            ),
            message: 'Workflow instance logs retrieved successfully',
        );
    }
}

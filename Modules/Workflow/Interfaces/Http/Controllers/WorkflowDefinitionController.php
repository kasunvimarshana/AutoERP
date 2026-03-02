<?php

declare(strict_types=1);

namespace Modules\Workflow\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Workflow\Application\Commands\CreateWorkflowDefinitionCommand;
use Modules\Workflow\Application\Commands\DeleteWorkflowDefinitionCommand;
use Modules\Workflow\Application\Commands\UpdateWorkflowDefinitionCommand;
use Modules\Workflow\Application\Services\WorkflowDefinitionService;
use Modules\Workflow\Interfaces\Http\Requests\CreateWorkflowDefinitionRequest;
use Modules\Workflow\Interfaces\Http\Requests\UpdateWorkflowDefinitionRequest;
use Modules\Workflow\Interfaces\Http\Resources\WorkflowDefinitionResource;
use Modules\Workflow\Interfaces\Http\Resources\WorkflowStateResource;
use Modules\Workflow\Interfaces\Http\Resources\WorkflowTransitionResource;

class WorkflowDefinitionController extends BaseController
{
    public function __construct(
        private readonly WorkflowDefinitionService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAllDefinitions($tenantId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($def) => (new WorkflowDefinitionResource($def))->resolve(),
                $result['items']
            ),
            message: 'Workflow definitions retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateWorkflowDefinitionRequest $request): JsonResponse
    {
        try {
            $definition = $this->service->createDefinition(new CreateWorkflowDefinitionCommand(
                tenantId: $request->validated('tenant_id'),
                name: $request->validated('name'),
                description: $request->validated('description'),
                entityType: $request->validated('entity_type'),
                states: $request->validated('states'),
                transitions: $request->validated('transitions'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new WorkflowDefinitionResource($definition))->resolve(),
            message: 'Workflow definition created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $definition = $this->service->findDefinitionById($id, $tenantId);

        if ($definition === null) {
            return $this->error('Workflow definition not found', status: 404);
        }

        return $this->success(
            data: (new WorkflowDefinitionResource($definition))->resolve(),
            message: 'Workflow definition retrieved successfully',
        );
    }

    public function update(UpdateWorkflowDefinitionRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $definition = $this->service->updateDefinition(new UpdateWorkflowDefinitionCommand(
                id: $id,
                tenantId: $tenantId,
                name: $request->validated('name'),
                description: $request->validated('description'),
                isActive: $request->validated('is_active'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new WorkflowDefinitionResource($definition))->resolve(),
            message: 'Workflow definition updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteDefinition(new DeleteWorkflowDefinitionCommand($id, $tenantId));

            return $this->success(message: 'Workflow definition deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function states(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $states = $this->service->findDefinitionStates($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($s) => (new WorkflowStateResource($s))->resolve(),
                $states
            ),
            message: 'Workflow states retrieved successfully',
        );
    }

    public function transitions(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $transitions = $this->service->findDefinitionTransitions($id, $tenantId);

        return $this->success(
            data: array_map(
                fn ($t) => (new WorkflowTransitionResource($t))->resolve(),
                $transitions
            ),
            message: 'Workflow transitions retrieved successfully',
        );
    }
}

<?php

namespace Modules\Workflow\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Workflow\Application\UseCases\CreateWorkflowUseCase;
use Modules\Workflow\Application\UseCases\TransitionWorkflowUseCase;
use Modules\Workflow\Domain\Contracts\WorkflowHistoryRepositoryInterface;
use Modules\Workflow\Domain\Contracts\WorkflowRepositoryInterface;
use Modules\Workflow\Presentation\Requests\StoreWorkflowRequest;
use Modules\Workflow\Presentation\Requests\TransitionWorkflowRequest;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowRepositoryInterface        $workflowRepo,
        private WorkflowHistoryRepositoryInterface $historyRepo,
        private CreateWorkflowUseCase              $createUseCase,
        private TransitionWorkflowUseCase          $transitionUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->workflowRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreWorkflowRequest $request): JsonResponse
    {
        $workflow = $this->createUseCase->execute(array_merge(
            $request->validated(),
            ['tenant_id' => auth()->user()?->tenant_id]
        ));

        return response()->json($workflow, 201);
    }

    public function show(string $id): JsonResponse
    {
        $workflow = $this->workflowRepo->findById($id);

        if (! $workflow) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($workflow);
    }

    public function update(StoreWorkflowRequest $request, string $id): JsonResponse
    {
        $workflow = $this->workflowRepo->update($id, $request->validated());

        return response()->json($workflow);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->workflowRepo->delete($id);

        return response()->json(null, 204);
    }

    public function transition(TransitionWorkflowRequest $request, string $id): JsonResponse
    {
        $history = $this->transitionUseCase->execute(array_merge(
            $request->validated(),
            [
                'tenant_id'   => auth()->user()?->tenant_id,
                'workflow_id' => $id,
                'actor_id'    => auth()->id(),
            ]
        ));

        return response()->json($history, 201);
    }

    public function history(string $id): JsonResponse
    {
        $workflow = $this->workflowRepo->findById($id);

        if (! $workflow) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $history = $this->historyRepo->findByDocument(
            request()->input('document_type', ''),
            request()->input('document_id', '')
        );

        return response()->json($history);
    }
}

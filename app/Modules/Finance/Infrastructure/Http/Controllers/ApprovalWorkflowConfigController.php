<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\DeleteApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\FindApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Application\Contracts\UpdateApprovalWorkflowConfigServiceInterface;
use Modules\Finance\Domain\Entities\ApprovalWorkflowConfig;
use Modules\Finance\Infrastructure\Http\Requests\ListApprovalWorkflowConfigRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreApprovalWorkflowConfigRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateApprovalWorkflowConfigRequest;
use Modules\Finance\Infrastructure\Http\Resources\ApprovalWorkflowConfigCollection;
use Modules\Finance\Infrastructure\Http\Resources\ApprovalWorkflowConfigResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApprovalWorkflowConfigController extends AuthorizedController
{
    public function __construct(
        private readonly CreateApprovalWorkflowConfigServiceInterface $createService,
        private readonly UpdateApprovalWorkflowConfigServiceInterface $updateService,
        private readonly DeleteApprovalWorkflowConfigServiceInterface $deleteService,
        private readonly FindApprovalWorkflowConfigServiceInterface $findService,
    ) {}

    public function index(ListApprovalWorkflowConfigRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ApprovalWorkflowConfig::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'module' => $validated['module'] ?? null,
            'entity_type' => $validated['entity_type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ApprovalWorkflowConfigCollection($items))->response();
    }

    public function store(StoreApprovalWorkflowConfigRequest $request): JsonResponse
    {
        $this->authorize('create', ApprovalWorkflowConfig::class);

        $config = $this->createService->execute($request->validated());

        return (new ApprovalWorkflowConfigResource($config))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $approvalWorkflowConfig): ApprovalWorkflowConfigResource
    {
        $found = $this->findOrFail($approvalWorkflowConfig);
        $this->authorize('view', $found);

        return new ApprovalWorkflowConfigResource($found);
    }

    public function update(UpdateApprovalWorkflowConfigRequest $request, int $approvalWorkflowConfig): ApprovalWorkflowConfigResource
    {
        $found = $this->findOrFail($approvalWorkflowConfig);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $approvalWorkflowConfig;

        return new ApprovalWorkflowConfigResource($this->updateService->execute($payload));
    }

    public function destroy(int $approvalWorkflowConfig): JsonResponse
    {
        $found = $this->findOrFail($approvalWorkflowConfig);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $approvalWorkflowConfig]);

        return Response::json(['message' => 'Approval workflow config deleted successfully']);
    }

    private function findOrFail(int $id): ApprovalWorkflowConfig
    {
        $config = $this->findService->find($id);

        if (! $config) {
            throw new NotFoundHttpException('Approval workflow config not found.');
        }

        return $config;
    }
}

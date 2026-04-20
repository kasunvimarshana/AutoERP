<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\ApproveApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CancelApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\CreateApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\DeleteApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\FindApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\RejectApprovalRequestServiceInterface;
use Modules\Finance\Application\Contracts\UpdateApprovalRequestServiceInterface;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Infrastructure\Http\Requests\ApproveApprovalRequestRequest;
use Modules\Finance\Infrastructure\Http\Requests\CancelApprovalRequestRequest;
use Modules\Finance\Infrastructure\Http\Requests\ListApprovalRequestRequest;
use Modules\Finance\Infrastructure\Http\Requests\RejectApprovalRequestRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreApprovalRequestRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateApprovalRequestRequest;
use Modules\Finance\Infrastructure\Http\Resources\ApprovalRequestCollection;
use Modules\Finance\Infrastructure\Http\Resources\ApprovalRequestResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApprovalRequestController extends AuthorizedController
{
    public function __construct(
        private readonly CreateApprovalRequestServiceInterface $createService,
        private readonly UpdateApprovalRequestServiceInterface $updateService,
        private readonly DeleteApprovalRequestServiceInterface $deleteService,
        private readonly FindApprovalRequestServiceInterface $findService,
        private readonly ApproveApprovalRequestServiceInterface $approveService,
        private readonly RejectApprovalRequestServiceInterface $rejectService,
        private readonly CancelApprovalRequestServiceInterface $cancelService,
    ) {}

    public function index(ListApprovalRequestRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ApprovalRequest::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'entity_type' => $validated['entity_type'] ?? null,
            'entity_id' => $validated['entity_id'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn (mixed $v): bool => $v !== null && $v !== '');

        $items = $this->findService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new ApprovalRequestCollection($items))->response();
    }

    public function store(StoreApprovalRequestRequest $request): JsonResponse
    {
        $this->authorize('create', ApprovalRequest::class);

        $ar = $this->createService->execute($request->validated());

        return (new ApprovalRequestResource($ar))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $approvalRequest): ApprovalRequestResource
    {
        $found = $this->findOrFail($approvalRequest);
        $this->authorize('view', $found);

        return new ApprovalRequestResource($found);
    }

    public function update(UpdateApprovalRequestRequest $request, int $approvalRequest): ApprovalRequestResource
    {
        $found = $this->findOrFail($approvalRequest);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $approvalRequest;

        return new ApprovalRequestResource($this->updateService->execute($payload));
    }

    public function destroy(int $approvalRequest): JsonResponse
    {
        $found = $this->findOrFail($approvalRequest);
        $this->authorize('delete', $found);

        $this->deleteService->execute(['id' => $approvalRequest]);

        return Response::json(['message' => 'Approval request deleted successfully']);
    }

    public function approve(ApproveApprovalRequestRequest $request, int $approvalRequest): ApprovalRequestResource
    {
        $found = $this->findOrFail($approvalRequest);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $approvalRequest;

        return new ApprovalRequestResource($this->approveService->execute($payload));
    }

    public function reject(RejectApprovalRequestRequest $request, int $approvalRequest): ApprovalRequestResource
    {
        $found = $this->findOrFail($approvalRequest);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $approvalRequest;

        return new ApprovalRequestResource($this->rejectService->execute($payload));
    }

    public function cancel(CancelApprovalRequestRequest $request, int $approvalRequest): ApprovalRequestResource
    {
        $found = $this->findOrFail($approvalRequest);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $approvalRequest;

        return new ApprovalRequestResource($this->cancelService->execute($payload));
    }

    private function findOrFail(int $id): ApprovalRequest
    {
        $ar = $this->findService->find($id);

        if (! $ar) {
            throw new NotFoundHttpException('Approval request not found.');
        }

        return $ar;
    }
}

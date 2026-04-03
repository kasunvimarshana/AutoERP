<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Returns\Application\Contracts\ApproveReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CancelReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CreateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\DeleteReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\ExpireReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\FindReturnAuthorizationServiceInterface;
use Modules\Returns\Application\DTOs\ReturnAuthorizationData;
use Modules\Returns\Infrastructure\Http\Requests\StoreReturnAuthorizationRequest;
use Modules\Returns\Infrastructure\Http\Resources\ReturnAuthorizationCollection;
use Modules\Returns\Infrastructure\Http\Resources\ReturnAuthorizationResource;

class ReturnAuthorizationController extends AuthorizedController
{
    public function __construct(
        protected FindReturnAuthorizationServiceInterface $findService,
        protected CreateReturnAuthorizationServiceInterface $createService,
        protected DeleteReturnAuthorizationServiceInterface $deleteService,
        protected ApproveReturnAuthorizationServiceInterface $approveService,
        protected CancelReturnAuthorizationServiceInterface $cancelService,
        protected ExpireReturnAuthorizationServiceInterface $expireService,
    ) {}

    public function index(Request $request): ReturnAuthorizationCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'return_type', 'party_id']);
        return new ReturnAuthorizationCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreReturnAuthorizationRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = ReturnAuthorizationData::fromArray([
            'tenantId'   => $v['tenant_id'],
            'rmaNumber'  => $v['rma_number'],
            'returnType' => $v['return_type'],
            'partyId'    => $v['party_id'],
            'partyType'  => $v['party_type'],
            'reason'     => $v['reason'] ?? null,
            'expiresAt'  => $v['expires_at'] ?? null,
            'notes'      => $v['notes'] ?? null,
            'metadata'   => $v['metadata'] ?? null,
            'status'     => $v['status'] ?? 'pending',
        ]);

        $authorization = $this->createService->execute($dto->toArray());
        return (new ReturnAuthorizationResource($authorization))->response()->setStatusCode(201);
    }

    public function show(int $id): ReturnAuthorizationResource|JsonResponse
    {
        $authorization = $this->findService->find($id);
        if (! $authorization) { return response()->json(['message' => 'Not found'], 404); }
        return new ReturnAuthorizationResource($authorization);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Return authorization deleted successfully']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $authorization = $this->approveService->execute([
            'id'            => $id,
            'authorized_by' => $request->integer('authorized_by'),
            'expires_at'    => $request->input('expires_at'),
        ]);
        return (new ReturnAuthorizationResource($authorization))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $authorization = $this->cancelService->execute(['id' => $id]);
        return (new ReturnAuthorizationResource($authorization))->response();
    }

    public function expire(int $id): JsonResponse
    {
        $authorization = $this->expireService->execute(['id' => $id]);
        return (new ReturnAuthorizationResource($authorization))->response();
    }
}

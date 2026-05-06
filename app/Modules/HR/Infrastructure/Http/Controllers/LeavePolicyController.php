<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\FindLeavePolicyServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeavePolicyServiceInterface;
use Modules\HR\Domain\Entities\LeavePolicy;
use Modules\HR\Infrastructure\Http\Requests\StoreLeavePolicyRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateLeavePolicyRequest;
use Modules\HR\Infrastructure\Http\Resources\LeavePolicyResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeavePolicyController extends AuthorizedController
{
    public function __construct(
        protected CreateLeavePolicyServiceInterface $createService,
        protected UpdateLeavePolicyServiceInterface $updateService,
        protected FindLeavePolicyServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->findService->list();

        return Response::json(['data' => LeavePolicyResource::collection($result)]);
    }

    public function store(StoreLeavePolicyRequest $request): JsonResponse
    {
        $entity = $this->createService->execute($request->validated());

        return (new LeavePolicyResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $leavePolicy): LeavePolicyResource
    {
        return new LeavePolicyResource($this->findOrFail($leavePolicy));
    }

    public function update(UpdateLeavePolicyRequest $request, int $leavePolicy): LeavePolicyResource
    {
        $this->findOrFail($leavePolicy);
        $payload = $request->validated();
        $payload['id'] = $leavePolicy;
        $updated = $this->updateService->execute($payload);

        return new LeavePolicyResource($updated);
    }

    public function destroy(int $leavePolicy): JsonResponse
    {
        $this->findOrFail($leavePolicy);

        return Response::json(null, 204);
    }

    private function findOrFail(int $id): LeavePolicy
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Leave policy not found.');
        }

        return $entity;
    }
}

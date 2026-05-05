<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreateLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\DeleteLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\FindLeaveTypeServiceInterface;
use Modules\HR\Application\Contracts\UpdateLeaveTypeServiceInterface;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Infrastructure\Http\Requests\StoreLeaveTypeRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateLeaveTypeRequest;
use Modules\HR\Infrastructure\Http\Resources\LeaveTypeResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LeaveTypeController extends AuthorizedController
{
    public function __construct(
        protected CreateLeaveTypeServiceInterface $createService,
        protected UpdateLeaveTypeServiceInterface $updateService,
        protected DeleteLeaveTypeServiceInterface $deleteService,
        protected FindLeaveTypeServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', LeaveType::class);
        $result = $this->findService->list();

        return Response::json(['data' => LeaveTypeResource::collection($result)]);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $this->authorize('create', LeaveType::class);
        $entity = $this->createService->execute($request->validated());

        return (new LeaveTypeResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $leaveType): LeaveTypeResource
    {
        $entity = $this->findOrFail($leaveType);
        $this->authorize('view', $entity);

        return new LeaveTypeResource($entity);
    }

    public function update(UpdateLeaveTypeRequest $request, int $leaveType): LeaveTypeResource
    {
        $entity = $this->findOrFail($leaveType);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $leaveType;
        $updated = $this->updateService->execute($payload);

        return new LeaveTypeResource($updated);
    }

    public function destroy(int $leaveType): JsonResponse
    {
        $entity = $this->findOrFail($leaveType);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $leaveType]);

        return Response::json(null, 204);
    }

    private function findOrFail(int $id): LeaveType
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Leave type not found.');
        }

        return $entity;
    }
}

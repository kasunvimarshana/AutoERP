<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\AssignShiftServiceInterface;
use Modules\HR\Application\Contracts\CreateShiftServiceInterface;
use Modules\HR\Application\Contracts\DeleteShiftServiceInterface;
use Modules\HR\Application\Contracts\FindShiftServiceInterface;
use Modules\HR\Application\Contracts\UpdateShiftServiceInterface;
use Modules\HR\Domain\Entities\Shift;
use Modules\HR\Infrastructure\Http\Requests\AssignShiftRequest;
use Modules\HR\Infrastructure\Http\Requests\StoreShiftRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdateShiftRequest;
use Modules\HR\Infrastructure\Http\Resources\ShiftAssignmentResource;
use Modules\HR\Infrastructure\Http\Resources\ShiftResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShiftController extends AuthorizedController
{
    public function __construct(
        protected CreateShiftServiceInterface $createService,
        protected UpdateShiftServiceInterface $updateService,
        protected DeleteShiftServiceInterface $deleteService,
        protected FindShiftServiceInterface $findService,
        protected AssignShiftServiceInterface $assignService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Shift::class);
        $result = $this->findService->list();

        return Response::json(['data' => ShiftResource::collection($result)]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $this->authorize('create', Shift::class);
        $entity = $this->createService->execute($request->validated());

        return (new ShiftResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $shift): ShiftResource
    {
        $entity = $this->findOrFail($shift);
        $this->authorize('view', $entity);

        return new ShiftResource($entity);
    }

    public function update(UpdateShiftRequest $request, int $shift): ShiftResource
    {
        $entity = $this->findOrFail($shift);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $shift;
        $updated = $this->updateService->execute($payload);

        return new ShiftResource($updated);
    }

    public function destroy(int $shift): JsonResponse
    {
        $entity = $this->findOrFail($shift);
        $this->authorize('delete', $entity);
        $this->deleteService->execute(['id' => $shift]);

        return Response::json(null, 204);
    }

    public function assign(AssignShiftRequest $request, int $shift): JsonResponse
    {
        $entity = $this->findOrFail($shift);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['shift_id'] = $shift;
        $assignment = $this->assignService->execute($payload);

        return (new ShiftAssignmentResource($assignment))->response()->setStatusCode(201);
    }

    private function findOrFail(int $id): Shift
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Shift not found.');
        }

        return $entity;
    }
}

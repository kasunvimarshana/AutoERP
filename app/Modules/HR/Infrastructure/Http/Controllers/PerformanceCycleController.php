<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\HR\Application\Contracts\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\FindPerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UpdatePerformanceCycleServiceInterface;
use Modules\HR\Domain\Entities\PerformanceCycle;
use Modules\HR\Infrastructure\Http\Requests\StorePerformanceCycleRequest;
use Modules\HR\Infrastructure\Http\Requests\UpdatePerformanceCycleRequest;
use Modules\HR\Infrastructure\Http\Resources\PerformanceCycleResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PerformanceCycleController extends AuthorizedController
{
    public function __construct(
        protected CreatePerformanceCycleServiceInterface $createService,
        protected UpdatePerformanceCycleServiceInterface $updateService,
        protected FindPerformanceCycleServiceInterface $findService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PerformanceCycle::class);
        $result = $this->findService->list();

        return Response::json(['data' => PerformanceCycleResource::collection($result)]);
    }

    public function store(StorePerformanceCycleRequest $request): JsonResponse
    {
        $this->authorize('create', PerformanceCycle::class);
        $entity = $this->createService->execute($request->validated());

        return (new PerformanceCycleResource($entity))->response()->setStatusCode(201);
    }

    public function show(int $performanceCycle): PerformanceCycleResource
    {
        $entity = $this->findOrFail($performanceCycle);
        $this->authorize('view', $entity);

        return new PerformanceCycleResource($entity);
    }

    public function update(UpdatePerformanceCycleRequest $request, int $performanceCycle): PerformanceCycleResource
    {
        $entity = $this->findOrFail($performanceCycle);
        $this->authorize('update', $entity);
        $payload = $request->validated();
        $payload['id'] = $performanceCycle;
        $updated = $this->updateService->execute($payload);

        return new PerformanceCycleResource($updated);
    }

    public function destroy(int $performanceCycle): JsonResponse
    {
        $entity = $this->findOrFail($performanceCycle);
        $this->authorize('delete', $entity);

        return Response::json(null, 204);
    }

    private function findOrFail(int $id): PerformanceCycle
    {
        $entity = $this->findService->find($id);
        if (! $entity) {
            throw new NotFoundHttpException('Performance cycle not found.');
        }

        return $entity;
    }
}

<?php

declare(strict_types=1);

namespace Modules\HR\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\CreatePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\DeletePerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\GetPerformanceCycleServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\ListPerformanceCyclesServiceInterface;
use Modules\HR\Application\Contracts\UseCases\PerformanceCycles\UpdatePerformanceCycleServiceInterface;
use Modules\HR\Presentation\Http\Requests\ListPerformanceCycleRequest;
use Modules\HR\Presentation\Http\Requests\UpsertPerformanceCycleRequest;
use Modules\HR\Presentation\Http\Resources\PerformanceCycleResource;

final class PerformanceCycleController extends Controller
{
    public function __construct(
        private readonly ListPerformanceCyclesServiceInterface $listService,
        private readonly GetPerformanceCycleServiceInterface $getService,
        private readonly CreatePerformanceCycleServiceInterface $createService,
        private readonly UpdatePerformanceCycleServiceInterface $updateService,
        private readonly DeletePerformanceCycleServiceInterface $deleteService,
    ) {
    }

    public function index(ListPerformanceCycleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pagedResult = $result->valueOrFail();
        if (! $pagedResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => PerformanceCycleResource::collection($pagedResult->items)->resolve(),
            'meta' => [
                'total' => $pagedResult->total,
                'page' => $pagedResult->page,
                'per_page' => $pagedResult->perPage,
                'page_count' => $pagedResult->pageCount(),
                'has_more' => $pagedResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PerformanceCycleResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PerformanceCycleResource($result->valueOrFail());
    }

    public function store(UpsertPerformanceCycleRequest $request): JsonResponse|PerformanceCycleResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PerformanceCycleResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPerformanceCycleRequest $request, int|string $id): JsonResponse|PerformanceCycleResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'HR_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PerformanceCycleResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
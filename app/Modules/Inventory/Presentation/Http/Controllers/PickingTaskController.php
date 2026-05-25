<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\CreatePickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\DeletePickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\GetPickingTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\ListPickingTasksServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PickingTasks\UpdatePickingTaskServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListPickingTaskRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertPickingTaskRequest;
use Modules\Inventory\Presentation\Http\Resources\PickingTaskResource;

final class PickingTaskController extends Controller
{
    public function __construct(
        private readonly ListPickingTasksServiceInterface $listService,
        private readonly GetPickingTaskServiceInterface $getService,
        private readonly CreatePickingTaskServiceInterface $createService,
        private readonly UpdatePickingTaskServiceInterface $updateService,
        private readonly DeletePickingTaskServiceInterface $deleteService,
    ) {
    }

    public function index(ListPickingTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => PickingTaskResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PickingTaskResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PickingTaskResource($result->valueOrFail());
    }

    public function store(UpsertPickingTaskRequest $request): JsonResponse|PickingTaskResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PickingTaskResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPickingTaskRequest $request, int|string $id): JsonResponse|PickingTaskResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PickingTaskResource($result->valueOrFail());
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
<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\CreatePutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\DeletePutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\GetPutAwayTaskServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\ListPutAwayTasksServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\UpdatePutAwayTaskServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListPutAwayTaskRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertPutAwayTaskRequest;
use Modules\Inventory\Presentation\Http\Resources\PutAwayTaskResource;

final class PutAwayTaskController extends Controller
{
    public function __construct(
        private readonly ListPutAwayTasksServiceInterface $listService,
        private readonly GetPutAwayTaskServiceInterface $getService,
        private readonly CreatePutAwayTaskServiceInterface $createService,
        private readonly UpdatePutAwayTaskServiceInterface $updateService,
        private readonly DeletePutAwayTaskServiceInterface $deleteService,
    ) {
    }

    public function index(ListPutAwayTaskRequest $request): JsonResponse
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
            'data' => PutAwayTaskResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|PutAwayTaskResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new PutAwayTaskResource($result->valueOrFail());
    }

    public function store(UpsertPutAwayTaskRequest $request): JsonResponse|PutAwayTaskResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new PutAwayTaskResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertPutAwayTaskRequest $request, int|string $id): JsonResponse|PutAwayTaskResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new PutAwayTaskResource($result->valueOrFail());
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
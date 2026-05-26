<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\Batches\CreateBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\DeleteBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\GetBatchServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\ListBatchesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Batches\UpdateBatchServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListBatchRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertBatchRequest;
use Modules\Inventory\Presentation\Http\Resources\BatchResource;

final class BatchController extends Controller
{
    public function __construct(
        private readonly ListBatchesServiceInterface $listService,
        private readonly GetBatchServiceInterface $getService,
        private readonly CreateBatchServiceInterface $createService,
        private readonly UpdateBatchServiceInterface $updateService,
        private readonly DeleteBatchServiceInterface $deleteService,
    ) {
    }

    public function index(ListBatchRequest $request): JsonResponse
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
            'data' => BatchResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|BatchResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new BatchResource($result->valueOrFail());
    }

    public function store(UpsertBatchRequest $request): JsonResponse|BatchResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new BatchResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertBatchRequest $request, int|string $id): JsonResponse|BatchResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new BatchResource($result->valueOrFail());
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
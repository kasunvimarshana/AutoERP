<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\CreateItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\DeleteItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\GetItemCategoryServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\ListItemCategoriesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemCategories\UpdateItemCategoryServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemCategoryRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemCategoryRequest;
use Modules\Item\Presentation\Http\Resources\ItemCategoryResource;

final class ItemCategoryController extends Controller
{
    public function __construct(
        private readonly ListItemCategoriesServiceInterface $listService,
        private readonly GetItemCategoryServiceInterface $getService,
        private readonly CreateItemCategoryServiceInterface $createService,
        private readonly UpdateItemCategoryServiceInterface $updateService,
        private readonly DeleteItemCategoryServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemCategoryRequest $request): JsonResponse
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
            'data' => ItemCategoryResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemCategoryResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemCategoryResource($result->valueOrFail());
    }

    public function store(UpsertItemCategoryRequest $request): JsonResponse|ItemCategoryResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemCategoryResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemCategoryRequest $request, int|string $id): JsonResponse|ItemCategoryResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemCategoryResource($result->valueOrFail());
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

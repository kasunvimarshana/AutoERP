<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\CreateItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\DeleteItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\GetItemAttributeGroupServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\ListItemAttributeGroupsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeGroups\UpdateItemAttributeGroupServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemAttributeGroupRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemAttributeGroupRequest;
use Modules\Item\Presentation\Http\Resources\ItemAttributeGroupResource;

final class ItemAttributeGroupController extends Controller
{
    public function __construct(
        private readonly ListItemAttributeGroupsServiceInterface $listService,
        private readonly GetItemAttributeGroupServiceInterface $getService,
        private readonly CreateItemAttributeGroupServiceInterface $createService,
        private readonly UpdateItemAttributeGroupServiceInterface $updateService,
        private readonly DeleteItemAttributeGroupServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemAttributeGroupRequest $request): JsonResponse
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
            'data' => ItemAttributeGroupResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemAttributeGroupResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemAttributeGroupResource($result->valueOrFail());
    }

    public function store(UpsertItemAttributeGroupRequest $request): JsonResponse|ItemAttributeGroupResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemAttributeGroupResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemAttributeGroupRequest $request, int|string $id): JsonResponse|ItemAttributeGroupResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemAttributeGroupResource($result->valueOrFail());
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

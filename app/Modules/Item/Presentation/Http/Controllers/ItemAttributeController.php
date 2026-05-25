<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\CreateItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\DeleteItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\GetItemAttributeServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\ListItemAttributesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributes\UpdateItemAttributeServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemAttributeRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemAttributeRequest;
use Modules\Item\Presentation\Http\Resources\ItemAttributeResource;

final class ItemAttributeController extends Controller
{
    public function __construct(
        private readonly ListItemAttributesServiceInterface $listService,
        private readonly GetItemAttributeServiceInterface $getService,
        private readonly CreateItemAttributeServiceInterface $createService,
        private readonly UpdateItemAttributeServiceInterface $updateService,
        private readonly DeleteItemAttributeServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemAttributeRequest $request): JsonResponse
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
            'data' => ItemAttributeResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemAttributeResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemAttributeResource($result->valueOrFail());
    }

    public function store(UpsertItemAttributeRequest $request): JsonResponse|ItemAttributeResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemAttributeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemAttributeRequest $request, int|string $id): JsonResponse|ItemAttributeResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemAttributeResource($result->valueOrFail());
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

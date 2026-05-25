<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\CreateItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\DeleteItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\GetItemAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\ListItemAttributeValuesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemAttributeValues\UpdateItemAttributeValueServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemAttributeValueRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemAttributeValueRequest;
use Modules\Item\Presentation\Http\Resources\ItemAttributeValueResource;

final class ItemAttributeValueController extends Controller
{
    public function __construct(
        private readonly ListItemAttributeValuesServiceInterface $listService,
        private readonly GetItemAttributeValueServiceInterface $getService,
        private readonly CreateItemAttributeValueServiceInterface $createService,
        private readonly UpdateItemAttributeValueServiceInterface $updateService,
        private readonly DeleteItemAttributeValueServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemAttributeValueRequest $request): JsonResponse
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
            'data' => ItemAttributeValueResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemAttributeValueResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemAttributeValueResource($result->valueOrFail());
    }

    public function store(UpsertItemAttributeValueRequest $request): JsonResponse|ItemAttributeValueResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemAttributeValueResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemAttributeValueRequest $request, int|string $id): JsonResponse|ItemAttributeValueResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemAttributeValueResource($result->valueOrFail());
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

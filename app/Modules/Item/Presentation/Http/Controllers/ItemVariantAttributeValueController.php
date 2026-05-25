<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\CreateItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\DeleteItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\GetItemVariantAttributeValueServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\ListItemVariantAttributeValuesServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemVariantAttributeValues\UpdateItemVariantAttributeValueServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemVariantAttributeValueRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemVariantAttributeValueRequest;
use Modules\Item\Presentation\Http\Resources\ItemVariantAttributeValueResource;

final class ItemVariantAttributeValueController extends Controller
{
    public function __construct(
        private readonly ListItemVariantAttributeValuesServiceInterface $listService,
        private readonly GetItemVariantAttributeValueServiceInterface $getService,
        private readonly CreateItemVariantAttributeValueServiceInterface $createService,
        private readonly UpdateItemVariantAttributeValueServiceInterface $updateService,
        private readonly DeleteItemVariantAttributeValueServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemVariantAttributeValueRequest $request): JsonResponse
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
            'data' => ItemVariantAttributeValueResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemVariantAttributeValueResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemVariantAttributeValueResource($result->valueOrFail());
    }

    public function store(UpsertItemVariantAttributeValueRequest $request): JsonResponse|ItemVariantAttributeValueResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemVariantAttributeValueResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemVariantAttributeValueRequest $request, int|string $id): JsonResponse|ItemVariantAttributeValueResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemVariantAttributeValueResource($result->valueOrFail());
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

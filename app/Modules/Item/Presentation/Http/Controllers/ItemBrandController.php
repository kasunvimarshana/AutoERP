<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\CreateItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\DeleteItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\GetItemBrandServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\ListItemBrandsServiceInterface;
use Modules\Item\Application\Contracts\UseCases\ItemBrands\UpdateItemBrandServiceInterface;
use Modules\Item\Presentation\Http\Requests\ListItemBrandRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemBrandRequest;
use Modules\Item\Presentation\Http\Resources\ItemBrandResource;

final class ItemBrandController extends Controller
{
    public function __construct(
        private readonly ListItemBrandsServiceInterface $listService,
        private readonly GetItemBrandServiceInterface $getService,
        private readonly CreateItemBrandServiceInterface $createService,
        private readonly UpdateItemBrandServiceInterface $updateService,
        private readonly DeleteItemBrandServiceInterface $deleteService,
    ) {
    }

    public function index(ListItemBrandRequest $request): JsonResponse
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
            'data' => ItemBrandResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|ItemBrandResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new ItemBrandResource($result->valueOrFail());
    }

    public function store(UpsertItemBrandRequest $request): JsonResponse|ItemBrandResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new ItemBrandResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertItemBrandRequest $request, int|string $id): JsonResponse|ItemBrandResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'ITEM_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new ItemBrandResource($result->valueOrFail());
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

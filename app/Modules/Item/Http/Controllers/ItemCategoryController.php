<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemCategoryRequest;
use Modules\Item\Http\Requests\UpdateItemCategoryRequest;
use Modules\Item\Http\Resources\ItemCategoryResource;
use Modules\Item\Services\ItemCategoryService;

final class ItemCategoryController
{
    public function __construct(private readonly ItemCategoryService $service) {}

    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        return ItemCategoryResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(),
        ));
    }

    public function lookup(ListItemRequest $request): AnonymousResourceCollection
    {
        return ItemCategoryResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(), true,
        ));
    }

    public function store(StoreItemCategoryRequest $request): JsonResponse
    {
        return (new ItemCategoryResource($this->service->create(
            $request->payload(), $request->tenantId(), $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListItemRequest $request, int $item_category): ItemCategoryResource
    {
        return new ItemCategoryResource($this->service->find(
            $item_category, $request->tenantId(), $request->organizationUnitId(),
        ));
    }

    public function update(UpdateItemCategoryRequest $request, int $item_category): ItemCategoryResource
    {
        return new ItemCategoryResource($this->service->update(
            $this->service->find($item_category, $request->tenantId(), $request->organizationUnitId()),
            $request->payload(),
        ));
    }

    public function destroy(ListItemRequest $request, int $item_category): JsonResponse
    {
        $this->service->delete($this->service->find(
            $item_category, $request->tenantId(), $request->organizationUnitId(),
        ));
        return response()->json(null, 204);
    }
}

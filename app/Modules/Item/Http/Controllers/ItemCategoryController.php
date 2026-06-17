<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemCategoryRequest;
use Modules\Item\Http\Requests\UpdateItemCategoryRequest;
use Modules\Item\Http\Resources\ItemCategoryResource;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemCategoryService;

final class ItemCategoryController
{
    public function __construct(
        private readonly ItemCategoryService $service,
        private readonly ItemAuthorizationService $authorization,
    ) {}

    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return ItemCategoryResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(),
        ));
    }

    public function lookup(ListItemRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return ItemCategoryResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(), true,
        ));
    }

    public function store(StoreItemCategoryRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_CATEGORIES);

        return (new ItemCategoryResource($this->service->create(
            $request->payload(), $request->tenantId(), $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListItemRequest $request, int $item_category): ItemCategoryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return new ItemCategoryResource($this->service->find(
            $item_category, $request->tenantId(), $request->organizationUnitId(),
        ));
    }

    public function update(UpdateItemCategoryRequest $request, int $item_category): ItemCategoryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_CATEGORIES);

        return new ItemCategoryResource($this->service->update(
            $this->service->find($item_category, $request->tenantId(), $request->organizationUnitId()),
            $request->payload(),
        ));
    }

    public function destroy(ListItemRequest $request, int $item_category): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_CATEGORIES);

        $this->service->delete($this->service->find(
            $item_category, $request->tenantId(), $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }
}

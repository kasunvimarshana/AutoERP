<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemBrandRequest;
use Modules\Item\Http\Requests\UpdateItemBrandRequest;
use Modules\Item\Http\Resources\ItemBrandResource;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemBrandService;

final class ItemBrandController
{
    public function __construct(
        private readonly ItemBrandService $service,
        private readonly ItemAuthorizationService $authorization,
    ) {}

    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return ItemBrandResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(),
        ));
    }

    public function lookup(ListItemRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return ItemBrandResource::collection($this->service->paginate(
            $request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->perPage(), true,
        ));
    }

    public function store(StoreItemBrandRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_BRANDS);

        return (new ItemBrandResource($this->service->create(
            $request->payload(), $request->tenantId(), $request->organizationUnitId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListItemRequest $request, int $item_brand): ItemBrandResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);

        return new ItemBrandResource($this->service->find(
            $item_brand, $request->tenantId(), $request->organizationUnitId(),
        ));
    }

    public function update(UpdateItemBrandRequest $request, int $item_brand): ItemBrandResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_BRANDS);

        return new ItemBrandResource($this->service->update(
            $this->service->find($item_brand, $request->tenantId(), $request->organizationUnitId()),
            $request->payload(),
        ));
    }

    public function destroy(ListItemRequest $request, int $item_brand): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::MANAGE_BRANDS);

        $this->service->delete($this->service->find(
            $item_brand, $request->tenantId(), $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }
}

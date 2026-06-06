<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Requests\StoreItemRequest;
use Modules\Item\Http\Requests\StoreItemWithRelationsRequest;
use Modules\Item\Http\Requests\UpdateItemRequest;
use Modules\Item\Http\Resources\ItemResource;
use Modules\Item\Http\Resources\ItemSummaryResource;
use Modules\Item\Services\ItemCreationService;
use Modules\Item\Services\ItemQueryService;
use Modules\Item\Services\ItemUpdateService;

final class ItemController
{
    public function __construct(
        private readonly ItemQueryService $queries,
        private readonly ItemCreationService $creation,
        private readonly ItemUpdateService $updates,
    ) {}

    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        return ItemSummaryResource::collection($this->queries->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        return (new ItemResource($this->creation->create($request->toData())))
            ->response()
            ->setStatusCode(201);
    }

    public function storeWithRelations(StoreItemWithRelationsRequest $request): JsonResponse
    {
        return (new ItemResource($this->creation->create($request->toData())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListItemRequest $request, int $item): ItemResource
    {
        return new ItemResource($this->queries->find(
            $item,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateItemRequest $request, int $item): ItemResource
    {
        $model = $this->queries->item($item, $request->tenantId(), $request->organizationUnitId());
        $updated = $this->updates->update($model, $request->toData());

        return new ItemResource($updated->load(['category', 'brand', 'baseUom']));
    }

    public function destroy(ListItemRequest $request, int $item): JsonResponse
    {
        $this->queries->delete($this->queries->item(
            $item,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function activate(ListItemRequest $request, int $item): ItemResource
    {
        return $this->changeActive($request, $item, true);
    }

    public function deactivate(ListItemRequest $request, int $item): ItemResource
    {
        return $this->changeActive($request, $item, false);
    }

    public function lookup(ListItemRequest $request, ?string $kind = null): AnonymousResourceCollection
    {
        return ItemSummaryResource::collection($this->queries->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
            $kind ?? 'active',
        ));
    }

    private function changeActive(ListItemRequest $request, int $item, bool $isActive): ItemResource
    {
        $model = $this->queries->item($item, $request->tenantId(), $request->organizationUnitId());

        return new ItemResource($this->updates->setActive($model, $isActive)->load(['category', 'brand', 'baseUom']));
    }
}

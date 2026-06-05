<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Item\Application\Services\ItemService;
use Modules\Item\Presentation\Http\Requests\ListItemRequest;
use Modules\Item\Presentation\Http\Requests\UpsertItemRequest;
use Modules\Item\Presentation\Http\Resources\ItemListResource;
use Modules\Item\Presentation\Http\Resources\ItemResource;

final class ItemController extends Controller
{
    public function __construct(private readonly ItemService $items) {}

    public function index(ListItemRequest $request): AnonymousResourceCollection
    {
        return ItemListResource::collection($this->items->paginate($request->validated()));
    }

    public function show(int $item): ItemResource
    {
        return new ItemResource($this->items->find($item));
    }

    public function store(UpsertItemRequest $request): JsonResponse
    {
        return (new ItemResource($this->items->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpsertItemRequest $request, int $item): ItemResource
    {
        return new ItemResource($this->items->update($item, $request->validated()));
    }

    public function destroy(int $item): JsonResponse
    {
        $this->items->delete($item);

        return response()->json(null, 204);
    }
}

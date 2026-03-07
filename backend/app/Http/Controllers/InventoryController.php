<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends BaseController
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->inventoryService->paginate(
            perPage: (int) $request->get('per_page', 15),
            relations: ['product'],
            filters: $request->only(['product_id', 'warehouse_id']),
            sortBy: $request->get('sort_by', 'id'),
            sortDirection: $request->get('sort_direction', 'asc'),
            search: $request->get('search', '')
        );

        return $this->paginatedResponse(
            $paginator->through(fn ($i) => new InventoryResource($i))
        );
    }

    public function show(int $productId): JsonResponse
    {
        $inventory = $this->inventoryService->getForProduct($productId);

        if (!$inventory) {
            return $this->notFoundResponse('Inventory record not found');
        }

        return $this->successResponse(new InventoryResource($inventory->load('product')));
    }

    public function stockIn(InventoryRequest $request): JsonResponse
    {
        $inventory = $this->inventoryService->stockIn(
            $request->product_id,
            $request->quantity,
            $request->only(['notes', 'reference_type', 'reference_id'])
        );

        return $this->successResponse(new InventoryResource($inventory), 'Stock added');
    }

    public function stockOut(InventoryRequest $request): JsonResponse
    {
        $inventory = $this->inventoryService->stockOut(
            $request->product_id,
            $request->quantity,
            $request->only(['notes', 'reference_type', 'reference_id'])
        );

        return $this->successResponse(new InventoryResource($inventory), 'Stock removed');
    }

    public function adjust(InventoryRequest $request): JsonResponse
    {
        $inventory = $this->inventoryService->adjustStock(
            $request->product_id,
            $request->quantity,
            $request->notes ?? ''
        );

        return $this->successResponse(new InventoryResource($inventory), 'Inventory adjusted');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $items = $this->inventoryService->getLowStock(
            $request->integer('threshold', 10)
        );

        return $this->successResponse(InventoryResource::collection($items));
    }

    public function outOfStock(): JsonResponse
    {
        $items = $this->inventoryService->getOutOfStock();
        return $this->successResponse(InventoryResource::collection($items));
    }
}

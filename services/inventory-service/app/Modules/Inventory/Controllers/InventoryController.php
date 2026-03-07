<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\DTOs\InventoryDTO;
use App\Modules\Inventory\DTOs\StockAdjustmentDTO;
use App\Modules\Inventory\Requests\StoreInventoryRequest;
use App\Modules\Inventory\Requests\UpdateInventoryRequest;
use App\Modules\Inventory\Requests\StockAdjustmentRequest;
use App\Modules\Inventory\Resources\InventoryCollection;
use App\Modules\Inventory\Resources\InventoryResource;
use App\Modules\Inventory\Services\Interfaces\InventoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryServiceInterface $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'product_id', 'low_stock', 'sort_by', 'sort_dir']);
        $perPage = min((int) $request->get('per_page', 15), 100);

        $items = $this->inventoryService->listInventory($filters, $perPage);

        return (new InventoryCollection($items))->response();
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $dto = InventoryDTO::fromArray($request->validated());
        $item = $this->inventoryService->createInventoryItem($dto);

        return (new InventoryResource($item))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->inventoryService->getInventoryItem($id);
        return (new InventoryResource($item))->response();
    }

    public function update(UpdateInventoryRequest $request, int $id): JsonResponse
    {
        $item = $this->inventoryService->updateInventoryItem($id, $request->validated());
        return (new InventoryResource($item))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->inventoryService->deleteInventoryItem($id);
        return response()->json(['message' => 'Inventory item deleted successfully.']);
    }

    public function adjustStock(StockAdjustmentRequest $request, int $id): JsonResponse
    {
        $dto = StockAdjustmentDTO::fromArray(array_merge($request->validated(), ['inventory_item_id' => $id]));
        $item = $this->inventoryService->adjustStock($dto);
        return (new InventoryResource($item))->response();
    }

    public function reserveStock(Request $request, int $id): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $result = $this->inventoryService->reserveStock($id, $request->input('quantity'));

        if (!$result) {
            return response()->json(['message' => 'Insufficient stock available for reservation.'], 422);
        }

        return response()->json(['message' => 'Stock reserved successfully.']);
    }

    public function releaseStock(Request $request, int $id): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        $result = $this->inventoryService->releaseStock($id, $request->input('quantity'));

        return response()->json(['message' => $result ? 'Stock released successfully.' : 'Failed to release stock.']);
    }

    public function showByProduct(int $productId): JsonResponse
    {
        $item = $this->inventoryService->getInventoryByProduct($productId);
        if (!$item) {
            return response()->json(['message' => 'No inventory found for this product.'], 404);
        }
        return (new InventoryResource($item))->response();
    }
}

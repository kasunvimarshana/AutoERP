<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\AdjustStockLevelRequest;
use Modules\Inventory\Http\Resources\StockLevelResource;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Services\StockMovementService;

/**
 * Stock Level API Controller
 */
class StockLevelController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected StockMovementService $stockMovementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');

        if ($productId && $warehouseId) {
            $quantity = $this->inventoryService->getAvailableQuantity($productId, $warehouseId);

            return response()->json(['available_quantity' => $quantity]);
        }

        if ($productId) {
            $stockLevels = $this->inventoryService->getStockLevels($productId);

            return StockLevelResource::collection($stockLevels)->response();
        }

        if ($warehouseId) {
            $stockLevels = $this->inventoryService->getWarehouseStock($warehouseId);

            return StockLevelResource::collection($stockLevels)->response();
        }

        return response()->json(['message' => 'Please specify product_id or warehouse_id'], 400);
    }

    public function adjust(AdjustStockLevelRequest $request): JsonResponse
    {
        $movement = $this->stockMovementService->adjustStock($request->validated());

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'movement_id' => $movement->id,
        ], 200);
    }
}

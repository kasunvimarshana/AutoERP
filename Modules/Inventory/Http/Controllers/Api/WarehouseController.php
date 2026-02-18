<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\StoreWarehouseRequest;
use Modules\Inventory\Http\Requests\UpdateWarehouseRequest;
use Modules\Inventory\Http\Resources\WarehouseResource;
use Modules\Inventory\Services\InventoryService;
use Modules\Inventory\Services\WarehouseService;

/**
 * Warehouse API Controller
 */
class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService,
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $warehouses = $this->warehouseService->getPaginated($perPage);

        return WarehouseResource::collection($warehouses)->response();
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create($request->validated());

        return (new WarehouseResource($warehouse))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = $this->warehouseService->findById($id);

        if (! $warehouse) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        }

        return (new WarehouseResource($warehouse))->response();
    }

    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        $warehouse = $this->warehouseService->update($id, $request->validated());

        return (new WarehouseResource($warehouse))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->warehouseService->delete($id);

        if (! $deleted) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        }

        return response()->json(['message' => 'Warehouse deleted successfully'], 200);
    }

    public function stockSummary(int $id): JsonResponse
    {
        $warehouse = $this->warehouseService->findById($id);

        if (! $warehouse) {
            return response()->json(['message' => 'Warehouse not found'], 404);
        }

        $stockLevels = $this->inventoryService->getWarehouseStock($id);

        return response()->json([
            'warehouse' => new WarehouseResource($warehouse),
            'total_products' => $warehouse->getTotalProducts(),
            'total_quantity' => $warehouse->getTotalQuantity(),
            'utilization' => $warehouse->getUtilizationPercentage(),
            'stock_levels' => $stockLevels,
        ]);
    }
}

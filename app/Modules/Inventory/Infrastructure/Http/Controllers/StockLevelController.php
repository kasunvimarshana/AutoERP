<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Contracts\StockLevelServiceInterface;
use Modules\Inventory\Infrastructure\Http\Resources\StockLevelResource;

class StockLevelController extends Controller
{
    public function __construct(
        private readonly StockLevelServiceInterface $stockLevelService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $levels = $this->stockLevelService->getStockByWarehouse(
            $tenantId,
            $request->query('warehouse_id', ''),
        );
        return response()->json(StockLevelResource::collection($levels));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $level = $this->stockLevelService->createStockLevel($tenantId, $request->all());
        return response()->json(new StockLevelResource($level), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $level = $this->stockLevelService->getStockLevel($tenantId, $id);
        return response()->json(new StockLevelResource($level));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $level = $this->stockLevelService->updateStockLevel($tenantId, $id, $request->all());
        return response()->json(new StockLevelResource($level));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->stockLevelService->deleteStockLevel($tenantId, $id);
        return response()->json(null, 204);
    }

    public function adjust(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $delta = (float) $request->input('delta', 0);
        $level = $this->stockLevelService->adjustQuantity($tenantId, $id, $delta);
        return response()->json(new StockLevelResource($level));
    }

    public function reserve(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $qty = (float) $request->input('quantity', 0);
        $level = $this->stockLevelService->reserveQuantity($tenantId, $id, $qty);
        return response()->json(new StockLevelResource($level));
    }

    public function release(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $qty = (float) $request->input('quantity', 0);
        $level = $this->stockLevelService->releaseReservation($tenantId, $id, $qty);
        return response()->json(new StockLevelResource($level));
    }
}

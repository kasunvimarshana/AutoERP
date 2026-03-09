<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WarehouseService;
use App\Http\Requests\Warehouse\CreateWarehouseRequest;
use App\Http\Resources\Warehouse\WarehouseResource;
use App\Http\Resources\Stock\StockLevelResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouseService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId   = $request->attributes->get('tenant_id');
        $warehouses = $this->warehouseService->list($tenantId, $request->all());
        return response()->json([
            'data' => WarehouseResource::collection($warehouses),
        ]);
    }

    public function store(CreateWarehouseRequest $request): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $warehouse = $this->warehouseService->create($tenantId, $request->validated());
        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $warehouse = $this->warehouseService->findById($tenantId, $id);
        return (new WarehouseResource($warehouse))->response();
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $warehouse = $this->warehouseService->update($tenantId, $id, $request->all());
        return (new WarehouseResource($warehouse))->response();
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $this->warehouseService->delete($tenantId, $id);
        return response()->json(['message' => 'Warehouse deleted successfully.']);
    }

    public function stockSummary(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $summary  = $this->warehouseService->getStockSummary($tenantId, $id);
        return response()->json(['data' => $summary]);
    }
}

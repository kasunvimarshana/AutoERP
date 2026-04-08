<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\WarehouseServiceInterface;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseResource;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseServiceInterface $warehouseService,
        private readonly WarehouseLocationServiceInterface $warehouseLocationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(WarehouseResource::collection($this->warehouseService->getAllWarehouses($tenantId)));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $warehouse = $this->warehouseService->createWarehouse($tenantId, $request->all());
        return response()->json(new WarehouseResource($warehouse), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new WarehouseResource($this->warehouseService->getWarehouse($tenantId, $id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new WarehouseResource($this->warehouseService->updateWarehouse($tenantId, $id, $request->all())));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->warehouseService->deleteWarehouse($request->user()->tenant_id, $id);
        return response()->json(null, 204);
    }

    public function tree(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json($this->warehouseLocationService->getTree($tenantId, $id));
    }
}

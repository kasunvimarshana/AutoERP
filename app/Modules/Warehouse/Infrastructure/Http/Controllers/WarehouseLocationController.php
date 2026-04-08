<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseLocationResource;

class WarehouseLocationController extends Controller
{
    public function __construct(
        private readonly WarehouseLocationServiceInterface $warehouseLocationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $locations = $this->warehouseLocationService->getTree($tenantId, $request->query('warehouse_id', ''));
        return response()->json($locations);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $location = $this->warehouseLocationService->createLocation($tenantId, $request->all());
        return response()->json(new WarehouseLocationResource($location), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new WarehouseLocationResource($this->warehouseLocationService->getLocation($tenantId, $id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        return response()->json(new WarehouseLocationResource($this->warehouseLocationService->updateLocation($tenantId, $id, $request->all())));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->warehouseLocationService->deleteLocation($request->user()->tenant_id, $id);
        return response()->json(null, 204);
    }
}

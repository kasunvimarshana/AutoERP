<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CreateWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $warehouses = $this->warehouseService->list($tenantId, (int) $request->query('per_page', 15));

        return response()->json([
            'data' => WarehouseResource::collection($warehouses),
            'meta' => [
                'current_page' => $warehouses->currentPage(),
                'per_page'     => $warehouses->perPage(),
                'total'        => $warehouses->total(),
                'last_page'    => $warehouses->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $warehouse): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $warehouseModel = $this->warehouseService->findOrFail($warehouse, $tenantId);

        return response()->json([
            'data' => new WarehouseResource($warehouseModel),
        ]);
    }

    public function store(CreateWarehouseRequest $request): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');

        $warehouse = $this->warehouseService->create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return response()->json([
            'message' => 'Warehouse created successfully.',
            'data'    => new WarehouseResource($warehouse),
        ], 201);
    }

    public function update(UpdateWarehouseRequest $request, int $warehouse): JsonResponse
    {
        $tenantId       = $request->attributes->get('tenant_id');
        $warehouseModel = $this->warehouseService->findOrFail($warehouse, $tenantId);

        $updated = $this->warehouseService->update($warehouseModel, $request->validated());

        return response()->json([
            'message' => 'Warehouse updated successfully.',
            'data'    => new WarehouseResource($updated),
        ]);
    }

    public function destroy(Request $request, int $warehouse): JsonResponse
    {
        $tenantId       = $request->attributes->get('tenant_id');
        $warehouseModel = $this->warehouseService->findOrFail($warehouse, $tenantId);

        $this->warehouseService->delete($warehouseModel);

        return response()->json(['message' => 'Warehouse deleted successfully.']);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $warehouses = $this->warehouseService->list(
            $request->only(['search', 'is_active', 'sort_by', 'sort_dir']),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(WarehouseResource::collection($warehouses)->response()->getData(true));
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Warehouse::class);
        $data = array_merge($request->validated(), ['tenant_id' => app('current_tenant_id')]);
        $warehouse = $this->warehouseService->create($data);

        return response()->json(['message' => 'Warehouse created.', 'data' => new WarehouseResource($warehouse)], 201);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = $this->warehouseService->findById($id);

        return response()->json(['data' => new WarehouseResource($warehouse)]);
    }

    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        $this->authorize('update', $this->warehouseService->findById($id));
        $updated = $this->warehouseService->update($id, $request->validated());

        return response()->json(['message' => 'Warehouse updated.', 'data' => new WarehouseResource($updated)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('delete', $this->warehouseService->findById($id));
        $this->warehouseService->delete($id);

        return response()->json(['message' => 'Warehouse deleted.'], 204);
    }
}

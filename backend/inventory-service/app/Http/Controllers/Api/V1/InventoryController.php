<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Http\Requests\Inventory\UpdateInventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $inventory = $this->inventoryService->list(
            $request->only([
                'product_id', 'warehouse_id', 'product_name',
                'low_stock', 'sort_by', 'sort_dir',
            ]),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(InventoryResource::collection($inventory)->response()->getData(true));
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Inventory::class);

        $data = array_merge($request->validated(), ['tenant_id' => app('current_tenant_id')]);
        $inventory = $this->inventoryService->create($data);

        return response()->json([
            'message' => 'Inventory record created successfully.',
            'data' => new InventoryResource($inventory),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $inventory = $this->inventoryService->findById($id);

        return response()->json(['data' => new InventoryResource($inventory)]);
    }

    public function update(UpdateInventoryRequest $request, int $id): JsonResponse
    {
        $inventory = $this->inventoryService->findById($id);
        $this->authorize('update', $inventory);

        $updated = $this->inventoryService->update($id, $request->validated());

        return response()->json([
            'message' => 'Inventory updated successfully.',
            'data' => new InventoryResource($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $inventory = $this->inventoryService->findById($id);
        $this->authorize('delete', $inventory);

        $this->inventoryService->delete($id);

        return response()->json(['message' => 'Inventory record deleted successfully.'], 204);
    }

    public function adjust(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer',
            'reason' => 'sometimes|string|max:255',
        ]);

        $this->authorize('update', $this->inventoryService->findById($id));

        $updated = $this->inventoryService->adjust(
            $id,
            $request->integer('quantity'),
            $request->input('reason', ''),
        );

        return response()->json([
            'message' => 'Inventory adjusted successfully.',
            'data' => new InventoryResource($updated),
        ]);
    }

    public function reserve(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        // Service-to-service call validation
        $secret = $request->header('X-Service-Secret');
        if ($secret !== config('services.auth_service.shared_secret')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $inventory = $this->inventoryService->reserve(
                $request->integer('product_id'),
                $request->integer('warehouse_id'),
                $request->integer('quantity'),
            );

            return response()->json([
                'message' => 'Inventory reserved successfully.',
                'data' => new InventoryResource($inventory),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function release(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $secret = $request->header('X-Service-Secret');
        if ($secret !== config('services.auth_service.shared_secret')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $inventory = $this->inventoryService->release(
                $request->integer('product_id'),
                $request->integer('warehouse_id'),
                $request->integer('quantity'),
            );

            return response()->json([
                'message' => 'Inventory released successfully.',
                'data' => new InventoryResource($inventory),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

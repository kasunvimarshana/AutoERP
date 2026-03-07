<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Requests\CreateInventoryRequest;
use App\Modules\Inventory\Requests\UpdateInventoryRequest;
use App\Modules\Inventory\Resources\InventoryResource;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 15);
        $search  = $request->query('search');
        $sortBy  = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $filters = $request->query('filter', []);

        $inventory = $this->inventoryService->listInventory($perPage, $filters, $search, $sortBy, $sortDir);

        return InventoryResource::collection($inventory);
    }

    public function show(int $id): JsonResponse
    {
        $inventory = $this->inventoryService->getInventory($id);

        if (!$inventory) {
            return response()->json(['error' => 'Inventory record not found'], 404);
        }

        return response()->json(new InventoryResource($inventory));
    }

    public function store(CreateInventoryRequest $request): JsonResponse
    {
        try {
            $inventory = $this->inventoryService->createInventory($request->validated());
            return response()->json(new InventoryResource($inventory), 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateInventoryRequest $request, int $id): JsonResponse
    {
        try {
            $inventory = $this->inventoryService->updateInventory($id, $request->validated());
            return response()->json(new InventoryResource($inventory));
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->inventoryService->deleteInventory($id);

            if (!$deleted) {
                return response()->json(['error' => 'Inventory record not found'], 404);
            }

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function adjust(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'delta'  => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $inventory = $this->inventoryService->adjustStock($id, $request->integer('delta'), (string) $request->string('reason'));
            return response()->json(new InventoryResource($inventory));
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

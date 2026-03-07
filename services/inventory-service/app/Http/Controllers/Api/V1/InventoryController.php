<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\StockAdjustmentDTO;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\CreateInventoryItemRequest;
use App\Http\Requests\ReserveStockRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemCollection;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryTransactionResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $items = $this->inventoryService->list(
            tenantId: $tenantId,
            filters:  $request->query(),
            perPage:  (int) $request->query('per_page', 15),
        );

        return response()->json(new InventoryItemCollection($items));
    }

    public function show(Request $request, int $item): JsonResponse
    {
        $tenantId     = $request->attributes->get('tenant_id');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        return response()->json([
            'data' => new InventoryItemResource($inventoryItem),
        ]);
    }

    public function store(CreateInventoryItemRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $claims   = $request->attributes->get('jwt_claims');

        $item = $this->inventoryService->create(array_merge(
            $request->validated(),
            [
                'tenant_id'    => $tenantId,
                'performed_by' => $claims ? (int) ($claims->sub ?? null) : null,
            ]
        ));

        return response()->json([
            'message' => 'Inventory item created successfully.',
            'data'    => new InventoryItemResource($item),
        ], 201);
    }

    public function update(UpdateInventoryItemRequest $request, int $item): JsonResponse
    {
        $tenantId      = $request->attributes->get('tenant_id');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        $updated = $this->inventoryService->update($inventoryItem, $request->validated());

        return response()->json([
            'message' => 'Inventory item updated successfully.',
            'data'    => new InventoryItemResource($updated),
        ]);
    }

    public function destroy(Request $request, int $item): JsonResponse
    {
        $tenantId      = $request->attributes->get('tenant_id');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        $this->inventoryService->delete($inventoryItem);

        return response()->json(['message' => 'Inventory item deleted successfully.']);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Operations
    |--------------------------------------------------------------------------
    */

    public function adjustStock(AdjustStockRequest $request, int $item): JsonResponse
    {
        $tenantId      = $request->attributes->get('tenant_id');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        $updated = $this->inventoryService->adjustStock($inventoryItem, $request->toDTO(
            performedBy: $claims ? (int) ($claims->sub ?? null) : null
        ));

        return response()->json([
            'message' => 'Stock adjusted successfully.',
            'data'    => new InventoryItemResource($updated),
        ]);
    }

    public function reserveStock(ReserveStockRequest $request, int $item): JsonResponse
    {
        $tenantId      = $request->attributes->get('tenant_id');
        $claims        = $request->attributes->get('jwt_claims');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        $validated = $request->validated();

        $updated = $this->inventoryService->reserveStock(
            item:          $inventoryItem,
            quantity:      (int) $validated['quantity'],
            reason:        $validated['reason'],
            referenceType: $validated['reference_type'] ?? null,
            referenceId:   $validated['reference_id'] ?? null,
            performedBy:   $claims ? (int) ($claims->sub ?? null) : null,
        );

        return response()->json([
            'message' => 'Stock reserved successfully.',
            'data'    => new InventoryItemResource($updated),
        ]);
    }

    public function releaseStock(ReserveStockRequest $request, int $item): JsonResponse
    {
        $tenantId      = $request->attributes->get('tenant_id');
        $claims        = $request->attributes->get('jwt_claims');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        $validated = $request->validated();

        $updated = $this->inventoryService->releaseStock(
            item:          $inventoryItem,
            quantity:      (int) $validated['quantity'],
            reason:        $validated['reason'],
            referenceType: $validated['reference_type'] ?? null,
            referenceId:   $validated['reference_id'] ?? null,
            performedBy:   $claims ? (int) ($claims->sub ?? null) : null,
        );

        return response()->json([
            'message' => 'Stock released successfully.',
            'data'    => new InventoryItemResource($updated),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Audit Trail
    |--------------------------------------------------------------------------
    */

    public function transactions(Request $request, int $item): JsonResponse
    {
        $tenantId      = $request->attributes->get('tenant_id');
        $inventoryItem = $this->inventoryService->findOrFail($item, $tenantId);

        $transactions = $inventoryItem->transactions()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data' => InventoryTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page'     => $transactions->perPage(),
                'total'        => $transactions->total(),
                'last_page'    => $transactions->lastPage(),
            ],
        ]);
    }
}

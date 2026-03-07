<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\CreateInventoryRequest;
use App\Http\Requests\TransferStockRequest;
use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryController extends BaseController
{
    public function __construct(
        private readonly InventoryRepositoryInterface $repository,
        private readonly InventoryService             $inventoryService,
    ) {}

    // -------------------------------------------------------------------------
    // GET /api/inventory
    // -------------------------------------------------------------------------

    /**
     * List inventory items, optionally paginated and filtered.
     * Accepts: per_page, tenant_id (from middleware), warehouse_id, product_id,
     *          with_products (bool), search
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');

            $query = Inventory::query()
                ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                ->when($request->input('warehouse_id'), fn ($q, $v) => $q->where('warehouse_id', $v))
                ->when($request->input('product_id'),   fn ($q, $v) => $q->where('product_id', $v))
                ->with('warehouse')
                ->orderBy('created_at', 'desc');

            $result = $this->repository->paginateConditional($query, $request);

            return $this->paginatedResponse($result, 'Inventory retrieved successfully.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@index', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve inventory.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/inventory
    // -------------------------------------------------------------------------

    public function store(CreateInventoryRequest $request): JsonResponse
    {
        try {
            $tenantId = $request->attributes->get('tenant_id');

            $inventory = $this->repository->create(array_merge(
                $request->validated(),
                ['tenant_id' => $tenantId]
            ));

            return $this->createdResponse($inventory, 'Inventory record created.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@store', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to create inventory record.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/inventory/{id}
    // -------------------------------------------------------------------------

    public function show(string $id): JsonResponse
    {
        $inventory = $this->repository->find($id);

        if (! $inventory) {
            return $this->notFoundResponse('Inventory record not found.');
        }

        $inventory->load('warehouse', 'stockMovements');

        return $this->successResponse($inventory, 'Inventory record retrieved.');
    }

    // -------------------------------------------------------------------------
    // PUT|PATCH /api/inventory/{id}
    // -------------------------------------------------------------------------

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'unit_cost'    => ['sometimes', 'numeric', 'min:0'],
            'warehouse_id' => ['sometimes', 'uuid'],
        ]);

        try {
            $inventory = $this->repository->update($id, $request->only(['unit_cost', 'warehouse_id']));

            return $this->successResponse($inventory, 'Inventory record updated.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Inventory record not found.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@update', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to update inventory record.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // DELETE /api/inventory/{id}
    // -------------------------------------------------------------------------

    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->repository->delete($id);

            if (! $deleted) {
                return $this->notFoundResponse('Inventory record not found.');
            }

            return $this->successResponse(null, 'Inventory record deleted.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Inventory record not found.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@destroy', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to delete inventory record.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/inventory/{id}/adjust
    // -------------------------------------------------------------------------

    public function adjust(AdjustStockRequest $request, string $id): JsonResponse
    {
        try {
            $performedBy = $request->user()?->id;
            $validated   = $request->validated();

            $inventory = $this->inventoryService->adjustStock(
                inventoryId: $id,
                quantity:    $validated['quantity'],
                type:        $validated['type'],
                reason:      $validated['reason'],
                performedBy: $performedBy,
            );

            return $this->successResponse($inventory, 'Stock adjusted successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Inventory record not found.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@adjust', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->errorResponse('Failed to adjust stock.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/inventory/transfer
    // -------------------------------------------------------------------------

    public function transfer(TransferStockRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->inventoryService->transferStock(
                fromInventoryId: $validated['from_inventory_id'],
                toInventoryId:   $validated['to_inventory_id'],
                quantity:        $validated['quantity'],
                notes:           $validated['notes'] ?? null,
                performedBy:     $request->user()?->id,
            );

            return $this->successResponse($result, 'Stock transferred successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\RuntimeException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('InventoryController@transfer', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to transfer stock.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/inventory/low-stock
    // -------------------------------------------------------------------------

    public function lowStock(Request $request): JsonResponse
    {
        try {
            $tenantId  = $request->attributes->get('tenant_id');
            $threshold = $request->integer('threshold');
            $items     = $this->repository->getLowStockItems($threshold ?: null, $tenantId ?: null);

            return $this->paginatedResponse($items, 'Low stock items retrieved.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@lowStock', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve low stock items.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/inventory/with-product-details
    // -------------------------------------------------------------------------

    public function withProductDetails(Request $request): JsonResponse
    {
        try {
            $result = $this->inventoryService->getInventoryWithProductDetails($request);

            return $this->paginatedResponse($result, 'Inventory with product details retrieved.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@withProductDetails', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to retrieve enriched inventory.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/inventory/reserve
    // -------------------------------------------------------------------------

    public function reserve(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'   => ['required', 'uuid'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'reference_id' => ['required', 'string'],
            'warehouse_id' => ['sometimes', 'uuid'],
        ]);

        try {
            $tenantId = $request->attributes->get('tenant_id');

            $inventory = $this->inventoryService->reserveStock(
                productId:   $request->input('product_id'),
                quantity:    $request->integer('quantity'),
                referenceId: $request->input('reference_id'),
                tenantId:    $tenantId,
                warehouseId: $request->input('warehouse_id'),
                performedBy: $request->user()?->id,
            );

            return $this->successResponse($inventory, 'Stock reserved successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Inventory record not found for this product.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@reserve', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to reserve stock.', null, 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/inventory/release
    // -------------------------------------------------------------------------

    public function release(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'   => ['required', 'uuid'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'reference_id' => ['required', 'string'],
            'warehouse_id' => ['sometimes', 'uuid'],
        ]);

        try {
            $tenantId = $request->attributes->get('tenant_id');

            $inventory = $this->inventoryService->releaseStock(
                productId:   $request->input('product_id'),
                quantity:    $request->integer('quantity'),
                referenceId: $request->input('reference_id'),
                tenantId:    $tenantId,
                warehouseId: $request->input('warehouse_id'),
                performedBy: $request->user()?->id,
            );

            return $this->successResponse($inventory, 'Stock released successfully.');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse('Inventory record not found for this product.');
        } catch (\Throwable $e) {
            Log::error('InventoryController@release', ['error' => $e->getMessage()]);

            return $this->errorResponse('Failed to release stock.', null, 500);
        }
    }
}

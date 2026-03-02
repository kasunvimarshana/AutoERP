<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Commands\AdjustStockCommand;
use Modules\Inventory\Application\Commands\TransferStockCommand;
use Modules\Inventory\Application\Handlers\AdjustStockHandler;
use Modules\Inventory\Application\Handlers\TransferStockHandler;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryInterface;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryRepositoryInterface $inventory,
        private readonly AdjustStockHandler           $adjustHandler,
        private readonly TransferStockHandler         $transferHandler,
    ) {}

    /**
     * GET /api/v1/inventory/stock/{productId}/{warehouseId}
     */
    public function getStockLevel(Request $request, int $productId, int $warehouseId): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $level    = $this->inventory->getStockLevel($productId, $warehouseId, $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'Stock level retrieved successfully.',
            'data'    => [
                'product_id'   => $productId,
                'warehouse_id' => $warehouseId,
                'quantity'     => $level,
            ],
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/inventory/adjust
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'   => 'required|integer|exists:products,id',
            'variant_id'   => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'quantity'     => 'required|numeric|min:0.0001',
            'type'         => 'required|string|in:IN,OUT,ADJUSTMENT_ADD,ADJUSTMENT_REMOVE',
            'reason'       => 'required|string|max:500',
            'unit_cost'    => 'nullable|numeric|min:0',
        ]);

        try {
            $entry = $this->adjustHandler->handle(new AdjustStockCommand(
                tenantId: (int) $request->attributes->get('tenant_id'),
                productId: (int) $validated['product_id'],
                variantId: isset($validated['variant_id']) ? (int) $validated['variant_id'] : null,
                warehouseId: (int) $validated['warehouse_id'],
                quantity: (string) $validated['quantity'],
                type: $validated['type'],
                reason: $validated['reason'],
                unitCost: (string) ($validated['unit_cost'] ?? '0'),
                createdBy: $request->user()?->id,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment recorded successfully.',
                'data'    => [
                    'entry_id'    => $entry->getId(),
                    'type'        => $entry->getType()->value,
                    'quantity'    => $entry->getQuantity(),
                    'created_at'  => $entry->getCreatedAt()->format('Y-m-d\TH:i:sP'),
                ],
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['stock' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * POST /api/v1/inventory/transfer
     */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'        => 'required|integer|exists:products,id',
            'variant_id'        => 'nullable|integer|exists:product_variants,id',
            'warehouse_from_id' => 'required|integer|exists:warehouses,id',
            'warehouse_to_id'   => 'required|integer|exists:warehouses,id|different:warehouse_from_id',
            'quantity'          => 'required|numeric|min:0.0001',
            'notes'             => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->transferHandler->handle(new TransferStockCommand(
                tenantId: (int) $request->attributes->get('tenant_id'),
                productId: (int) $validated['product_id'],
                variantId: isset($validated['variant_id']) ? (int) $validated['variant_id'] : null,
                warehouseFromId: (int) $validated['warehouse_from_id'],
                warehouseToId: (int) $validated['warehouse_to_id'],
                quantity: (string) $validated['quantity'],
                notes: $validated['notes'] ?? null,
                createdBy: $request->user()?->id,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer completed successfully.',
                'data'    => [
                    'out_entry_id' => $result['out']->getId(),
                    'in_entry_id'  => $result['in']->getId(),
                    'quantity'     => $validated['quantity'],
                ],
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['transfer' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * GET /api/v1/inventory/history
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'   => 'required|integer',
            'warehouse_id' => 'required|integer',
            'page'         => 'nullable|integer|min:1',
            'per_page'     => 'nullable|integer|min:1|max:100',
        ]);

        $tenantId   = (int) $request->attributes->get('tenant_id');
        $page       = (int) ($validated['page'] ?? 1);
        $perPage    = (int) ($validated['per_page'] ?? 25);

        $entries = $this->inventory->getHistory(
            $tenantId,
            (int) $validated['product_id'],
            (int) $validated['warehouse_id'],
            $page,
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock history retrieved successfully.',
            'data'    => array_map(fn ($e) => [
                'id'             => $e->getId(),
                'type'           => $e->getType()->value,
                'quantity'       => $e->getQuantity(),
                'unit_cost'      => $e->getUnitCost(),
                'reference_type' => $e->getReferenceType(),
                'reference_id'   => $e->getReferenceId(),
                'notes'          => $e->getNotes(),
                'created_at'     => $e->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            ], $entries),
            'errors'  => null,
        ]);
    }
}

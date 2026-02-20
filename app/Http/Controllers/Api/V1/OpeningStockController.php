<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Opening Stock Controller
 *
 * Handles the creation of initial stock levels for products when
 * a business starts using the system or adds new locations.
 * This wraps the stock adjustment service with an "opening_stock" reason.
 */
class OpeningStockController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.view'), 403);

        $tenantId = $request->user()->tenant_id;

        $items = \Illuminate\Support\Facades\DB::table('stock_movements')
            ->where('stock_movements.tenant_id', $tenantId)
            ->where('stock_movements.movement_type', 'opening_stock')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_movements.warehouse_id')
            ->select([
                'stock_movements.id',
                'stock_movements.product_id',
                'products.name as product_name',
                'stock_movements.warehouse_id',
                'warehouses.name as warehouse_name',
                'stock_movements.quantity',
                'stock_movements.cost_per_unit',
                'stock_movements.moved_at',
                'stock_movements.notes',
            ])
            ->orderByDesc('stock_movements.moved_at')
            ->paginate(min((int) $request->query('per_page', 15), 100));

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('inventory.adjust'), 403);

        $data = $request->validate([
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'date' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'lines.*.variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.cost_per_unit' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $userId = $request->user()->id;
        $movedAt = $data['date'] ?? now();

        $movements = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $tenantId, $userId, $movedAt) {
            $created = [];

            foreach ($data['lines'] as $line) {
                // Upsert stock item
                $stockItem = \App\Models\StockItem::lockForUpdate()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'warehouse_id' => $data['warehouse_id'],
                        'product_id' => $line['product_id'],
                        'variant_id' => $line['variant_id'] ?? null,
                    ],
                    [
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'quantity_available' => 0,
                        'cost_per_unit' => $line['cost_per_unit'] ?? 0,
                    ]
                );

                $qty = (string) $line['quantity'];
                $stockItem->quantity_on_hand = bcadd((string) $stockItem->quantity_on_hand, $qty, 8);
                $stockItem->quantity_available = bcsub(
                    (string) $stockItem->quantity_on_hand,
                    (string) $stockItem->quantity_reserved,
                    8
                );
                $stockItem->save();

                // Record as opening stock movement
                $movement = \App\Models\StockMovement::create([
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'] ?? null,
                    'movement_type' => 'opening_stock',
                    'quantity' => $qty,
                    'cost_per_unit' => $line['cost_per_unit'] ?? 0,
                    'notes' => $data['notes'] ?? 'Opening stock entry',
                    'user_id' => $userId,
                    'moved_at' => $movedAt,
                ]);

                $created[] = $movement;
            }

            return $created;
        });

        return response()->json(['created' => count($movements), 'movements' => $movements], 201);
    }
}

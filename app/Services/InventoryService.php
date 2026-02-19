<?php

namespace App\Services;

use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function paginateStock(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockItem::where('tenant_id', $tenantId)
            ->with(['product', 'variant', 'warehouse']);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->paginate($perPage);
    }

    public function adjust(
        string $tenantId,
        string $warehouseId,
        string $productId,
        string $quantity,
        string $movementType,
        ?string $variantId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use (
            $tenantId, $warehouseId, $productId, $quantity,
            $movementType, $variantId, $notes, $referenceType, $referenceId
        ) {
            // Pessimistic lock on stock item
            $stockItem = StockItem::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            if (! $stockItem) {
                $stockItem = StockItem::create([
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity_on_hand' => '0',
                    'quantity_reserved' => '0',
                    'quantity_available' => '0',
                    'cost_per_unit' => '0',
                    'currency' => 'USD',
                ]);
            }

            // Outbound movement types reduce stock; all others increase it
            $outbound = ['shipment', 'transfer_out'];
            $direction = in_array($movementType, $outbound, true) ? '-1' : '1';
            $newQty = bcadd($stockItem->quantity_on_hand, bcmul($quantity, $direction, 8), 8);

            if (bccomp($newQty, '0', 8) < 0) {
                throw new \RuntimeException('Insufficient stock. Available: '.$stockItem->quantity_on_hand);
            }

            // quantity_available = quantity_on_hand - quantity_reserved
            $newAvailable = bcsub($newQty, $stockItem->quantity_reserved, 8);

            $stockItem->update([
                'quantity_on_hand' => $newQty,
                'quantity_available' => $newAvailable,
            ]);

            return StockMovement::create([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => Auth::id(),
            ]);
        });
    }
}

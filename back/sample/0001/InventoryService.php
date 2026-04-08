<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * InventoryService
 *
 * Handles atomic stock mutations.
 *
 * Locking strategy
 * ────────────────
 * We use SELECT ... FOR UPDATE (pessimistic locking) to prevent
 * two concurrent reservations from over-selling the same product.
 * Rows are locked in a deterministic order (ORDER BY id) to avoid
 * deadlocks when multiple transactions lock overlapping product sets.
 */
class InventoryService
{
    /**
     * Atomically check availability and decrement stock.
     * Must be called inside an existing DB transaction.
     *
     * @param  array $items  [['product_id' => int, 'quantity' => int], ...]
     * @throws InsufficientStockException
     */
    public function checkAndReserveStock(array $items): void
    {
        $productIds = collect($items)->pluck('product_id')->unique()->sort()->values();

        // Lock rows in ascending ID order to prevent deadlocks
        $products = Product::whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new InsufficientStockException(
                    "Product #{$item['product_id']} not found."
                );
            }

            if ($product->available_stock < $item['quantity']) {
                throw new InsufficientStockException(
                    "Insufficient stock for product #{$product->id} " .
                    "({$product->name}). " .
                    "Requested: {$item['quantity']}, Available: {$product->available_stock}."
                );
            }

            // Decrement available stock
            $product->decrement('available_stock', $item['quantity']);
        }
    }

    /**
     * Return previously reserved stock back to the available pool.
     * Must be called inside an existing DB transaction.
     *
     * @param  array $items  [['product_id' => int, 'quantity' => int], ...]
     */
    public function releaseStock(array $items): void
    {
        $productIds = collect($items)->pluck('product_id')->unique()->sort()->values();

        // Lock in ascending ID order (same order as reserve) to avoid deadlocks
        $products = Product::whereIn('id', $productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            if ($product = $products->get($item['product_id'])) {
                $product->increment('available_stock', $item['quantity']);
            }
        }
    }
}

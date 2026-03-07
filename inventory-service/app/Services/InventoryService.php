<?php

namespace App\Services;

use App\Models\InventoryReservation;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Atomically reserve stock for all items in an order.
     *
     * @param  string  $sagaId
     * @param  string  $orderId
     * @param  array   $items  Each item: ['product_id' => uuid, 'quantity' => int, ...]
     * @return bool  true on success, false if any item has insufficient stock
     */
    public function reserveStock(string $sagaId, string $orderId, array $items): bool
    {
        return DB::transaction(function () use ($sagaId, $orderId, $items): bool {
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity  = (int) $item['quantity'];

                // Lock the row for update to prevent race conditions
                $product = Product::lockForUpdate()->find($productId);

                if (! $product) {
                    Log::warning('[InventoryService] Product not found during reservation', [
                        'product_id' => $productId,
                        'saga_id'    => $sagaId,
                    ]);
                    return false;
                }

                if ($product->getAvailableStock() < $quantity) {
                    Log::warning('[InventoryService] Insufficient stock', [
                        'product_id'     => $productId,
                        'requested'      => $quantity,
                        'available'      => $product->getAvailableStock(),
                        'saga_id'        => $sagaId,
                    ]);
                    return false;
                }

                InventoryReservation::create([
                    'order_id'   => $orderId,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'status'     => InventoryReservation::STATUS_PENDING,
                    'saga_id'    => $sagaId,
                ]);

                $product->increment('reserved_quantity', $quantity);

                Log::info('[InventoryService] Stock reserved', [
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'saga_id'    => $sagaId,
                ]);
            }

            return true;
        });
    }

    /**
     * Release all reservations for an order (compensating transaction).
     *
     * @param  string  $sagaId
     * @param  string  $orderId
     * @return bool
     */
    public function releaseReservation(string $sagaId, string $orderId): bool
    {
        return DB::transaction(function () use ($sagaId, $orderId): bool {
            $reservations = InventoryReservation::where('order_id', $orderId)
                ->where('saga_id', $sagaId)
                ->whereIn('status', [
                    InventoryReservation::STATUS_PENDING,
                    InventoryReservation::STATUS_CONFIRMED,
                ])
                ->lockForUpdate()
                ->get();

            if ($reservations->isEmpty()) {
                Log::info('[InventoryService] No reservations to release', [
                    'order_id' => $orderId,
                    'saga_id'  => $sagaId,
                ]);
                return true;
            }

            foreach ($reservations as $reservation) {
                $product = Product::lockForUpdate()->find($reservation->product_id);

                if ($product) {
                    $decrement = min($reservation->quantity, $product->reserved_quantity);
                    if ($decrement > 0) {
                        $product->decrement('reserved_quantity', $decrement);
                    }
                }

                $reservation->update(['status' => InventoryReservation::STATUS_RELEASED]);

                Log::info('[InventoryService] Reservation released', [
                    'reservation_id' => $reservation->id,
                    'product_id'     => $reservation->product_id,
                    'quantity'       => $reservation->quantity,
                    'saga_id'        => $sagaId,
                ]);
            }

            return true;
        });
    }

    /**
     * Confirm all pending reservations for a saga (called after payment succeeds).
     *
     * @param  string  $sagaId
     * @return bool
     */
    public function confirmReservation(string $sagaId): bool
    {
        $updated = InventoryReservation::where('saga_id', $sagaId)
            ->where('status', InventoryReservation::STATUS_PENDING)
            ->update(['status' => InventoryReservation::STATUS_CONFIRMED]);

        Log::info('[InventoryService] Reservations confirmed', [
            'saga_id' => $sagaId,
            'count'   => $updated,
        ]);

        return true;
    }
}

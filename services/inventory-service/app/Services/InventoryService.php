<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * InventoryService
 *
 * Core domain service for atomic stock reservations, releases, and fulfillments.
 *
 * The key invariant is that reserveStock() checks ALL requested items are
 * available BEFORE reserving ANY of them, ensuring no partial reservations
 * that would require compensation.  Everything executes inside a single
 * DB::transaction() so a mid-flight failure rolls the entire operation back.
 */
class InventoryService
{
    // -------------------------------------------------------------------------
    // Reserve
    // -------------------------------------------------------------------------

    /**
     * Atomically reserve stock for all items in a saga.
     *
     * @param  array<int, array{sku: string, quantity: int, warehouse_id?: string}>  $items
     * @return array{success: bool, reservations: array, errors: array}
     */
    public function reserveStock(
        string $sagaId,
        string $orderId,
        array  $items,
        string $tenantId
    ): array {
        try {
            return DB::transaction(function () use ($sagaId, $orderId, $items, $tenantId): array {
                // ---- Phase 1: Validate all items exist and have sufficient stock ----
                $resolved = [];
                $errors   = [];

                foreach ($items as $item) {
                    $sku        = $item['sku'];
                    $quantity   = (int) $item['quantity'];
                    $warehouseId = $item['warehouse_id'] ?? null;

                    $product = Product::byTenant($tenantId)
                        ->active()
                        ->where('sku', $sku)
                        ->first();

                    if ($product === null) {
                        $errors[] = "Product with SKU '{$sku}' not found for tenant '{$tenantId}'.";
                        continue;
                    }

                    $inventoryQuery = InventoryItem::where('product_id', $product->id)
                        ->byTenant($tenantId)
                        ->lockForUpdate();

                    if ($warehouseId !== null) {
                        $inventoryQuery->byWarehouse($warehouseId);
                    }

                    $inventoryItem = $inventoryQuery->first();

                    if ($inventoryItem === null) {
                        $errors[] = "No inventory record found for SKU '{$sku}'"
                            . ($warehouseId ? " in warehouse '{$warehouseId}'" : '') . '.';
                        continue;
                    }

                    if (!$inventoryItem->isAvailable($quantity)) {
                        $errors[] = "Insufficient stock for SKU '{$sku}': "
                            . "requested {$quantity}, available {$inventoryItem->quantity_available}.";
                        continue;
                    }

                    $resolved[] = [
                        'inventory_item' => $inventoryItem,
                        'quantity'       => $quantity,
                        'sku'            => $sku,
                    ];
                }

                // If any item failed validation, abort the entire transaction.
                if (!empty($errors)) {
                    return ['success' => false, 'reservations' => [], 'errors' => $errors];
                }

                // ---- Phase 2: Reserve all items (all checks passed) ----
                $reservations = [];

                foreach ($resolved as $entry) {
                    /** @var InventoryItem $inventoryItem */
                    $inventoryItem = $entry['inventory_item'];
                    $quantity      = $entry['quantity'];

                    $reserved = $inventoryItem->reserve($quantity);

                    if (!$reserved) {
                        // Race condition: another transaction snuck in. Roll back everything.
                        throw new \RuntimeException(
                            "Concurrent reservation conflict for SKU '{$entry['sku']}'. Rolling back."
                        );
                    }

                    $reservation = InventoryReservation::create([
                        'inventory_item_id' => $inventoryItem->id,
                        'order_id'          => $orderId,
                        'saga_id'           => $sagaId,
                        'quantity'          => $quantity,
                        'status'            => 'pending',
                        'expires_at'        => now()->addMinutes(30),
                    ]);

                    $reservations[] = [
                        'reservation_id'    => $reservation->id,
                        'inventory_item_id' => $inventoryItem->id,
                        'product_id'        => $inventoryItem->product_id,
                        'sku'               => $entry['sku'],
                        'quantity'          => $quantity,
                        'warehouse_id'      => $inventoryItem->warehouse_id,
                    ];
                }

                Log::info('[InventoryService] Stock reserved.', [
                    'saga_id'  => $sagaId,
                    'order_id' => $orderId,
                    'count'    => count($reservations),
                ]);

                return ['success' => true, 'reservations' => $reservations, 'errors' => []];
            });
        } catch (Throwable $e) {
            Log::error('[InventoryService] reserveStock failed.', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success'      => false,
                'reservations' => [],
                'errors'       => [$e->getMessage()],
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Release
    // -------------------------------------------------------------------------

    /**
     * Release all active reservations for a saga (compensation / rollback).
     */
    public function releaseStock(string $sagaId, string $orderId): void
    {
        DB::transaction(function () use ($sagaId, $orderId): void {
            $reservations = InventoryReservation::bySaga($sagaId)
                ->byOrder($orderId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->with('inventoryItem')
                ->get();

            foreach ($reservations as $reservation) {
                if ($reservation->inventoryItem === null) {
                    continue;
                }

                $reservation->inventoryItem->releaseQuantity($reservation->quantity);
                $reservation->release();
            }

            Log::info('[InventoryService] Stock released.', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'count'    => $reservations->count(),
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Fulfill
    // -------------------------------------------------------------------------

    /**
     * Fulfill all confirmed reservations for a saga (order confirmed / shipped).
     */
    public function fulfillStock(string $sagaId, string $orderId): void
    {
        DB::transaction(function () use ($sagaId, $orderId): void {
            $reservations = InventoryReservation::bySaga($sagaId)
                ->byOrder($orderId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->with('inventoryItem')
                ->get();

            foreach ($reservations as $reservation) {
                if ($reservation->inventoryItem === null) {
                    continue;
                }

                $reservation->inventoryItem->fulfillQuantity($reservation->quantity);
                $reservation->fulfill();
            }

            Log::info('[InventoryService] Stock fulfilled.', [
                'saga_id'  => $sagaId,
                'order_id' => $orderId,
                'count'    => $reservations->count(),
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Check availability
    // -------------------------------------------------------------------------

    /**
     * Check whether all requested items are available without reserving them.
     *
     * @param  array<int, array{sku: string, quantity: int, warehouse_id?: string}>  $items
     * @return array{available: bool, items: array}
     */
    public function checkAvailability(array $items, string $tenantId): array
    {
        $result = [];
        $allAvailable = true;

        foreach ($items as $item) {
            $sku        = $item['sku'];
            $quantity   = (int) ($item['quantity'] ?? 1);
            $warehouseId = $item['warehouse_id'] ?? null;

            $product = Product::byTenant($tenantId)->active()->where('sku', $sku)->first();

            if ($product === null) {
                $result[] = [
                    'sku'       => $sku,
                    'available' => false,
                    'reason'    => 'Product not found.',
                ];
                $allAvailable = false;
                continue;
            }

            $inventoryQuery = InventoryItem::where('product_id', $product->id)
                ->byTenant($tenantId);

            if ($warehouseId !== null) {
                $inventoryQuery->byWarehouse($warehouseId);
            }

            $inventoryItem = $inventoryQuery->first();

            if ($inventoryItem === null) {
                $result[] = [
                    'sku'       => $sku,
                    'available' => false,
                    'reason'    => 'No inventory record.',
                ];
                $allAvailable = false;
                continue;
            }

            $isAvailable = $inventoryItem->isAvailable($quantity);

            if (!$isAvailable) {
                $allAvailable = false;
            }

            $result[] = [
                'sku'                => $sku,
                'available'          => $isAvailable,
                'quantity_requested' => $quantity,
                'quantity_available' => $inventoryItem->quantity_available,
                'warehouse_id'       => $inventoryItem->warehouse_id,
            ];
        }

        return ['available' => $allAvailable, 'items' => $result];
    }
}

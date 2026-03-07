<?php

namespace App\Repositories;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class InventoryRepository extends BaseRepository
{
    protected array $searchableColumns = ['location', 'notes'];

    protected function model(): string
    {
        return Inventory::class;
    }

    public function findByProduct(int $productId): ?Inventory
    {
        return $this->newQuery()->where('product_id', $productId)->first();
    }

    /**
     * Atomically adjust inventory quantity and create a transaction log.
     */
    public function adjustQuantity(int $productId, int $quantityChange, string $type, array $meta = []): Inventory
    {
        return DB::transaction(function () use ($productId, $quantityChange, $type, $meta) {
            // Lock the row for update
            $inventory = $this->newQuery()
                              ->where('product_id', $productId)
                              ->lockForUpdate()
                              ->firstOrFail();

            $before = $inventory->quantity;
            $after  = $before + $quantityChange;

            if ($after < 0) {
                throw new \RuntimeException("Insufficient stock for product ID {$productId}. Available: {$before}");
            }

            $inventory->update([
                'quantity'           => $after,
                'available_quantity' => $after - $inventory->reserved_quantity,
            ]);

            InventoryTransaction::create(array_merge([
                'inventory_id'    => $inventory->id,
                'product_id'      => $productId,
                'tenant_id'       => $inventory->tenant_id,
                'user_id'         => auth()->id(),
                'type'            => $type,
                'quantity'        => abs($quantityChange),
                'quantity_before' => $before,
                'quantity_after'  => $after,
            ], $meta));

            return $inventory->fresh();
        });
    }

    /**
     * Reserve (hold) inventory for an order without reducing stock.
     */
    public function reserveQuantity(int $productId, int $qty): Inventory
    {
        return DB::transaction(function () use ($productId, $qty) {
            $inventory = $this->newQuery()
                              ->where('product_id', $productId)
                              ->lockForUpdate()
                              ->firstOrFail();

            $available = $inventory->quantity - $inventory->reserved_quantity;

            if ($available < $qty) {
                throw new \RuntimeException("Cannot reserve {$qty} units for product {$productId}. Available: {$available}");
            }

            $inventory->increment('reserved_quantity', $qty);
            $inventory->decrement('available_quantity', $qty);

            InventoryTransaction::create([
                'inventory_id'    => $inventory->id,
                'product_id'      => $productId,
                'tenant_id'       => $inventory->tenant_id,
                'user_id'         => auth()->id(),
                'type'            => InventoryTransaction::TYPE_RESERVED,
                'quantity'        => $qty,
                'quantity_before' => $inventory->getOriginal('reserved_quantity'),
                'quantity_after'  => $inventory->reserved_quantity,
            ]);

            return $inventory->fresh();
        });
    }

    /**
     * Release (undo) a reservation.
     */
    public function releaseReservation(int $productId, int $qty): Inventory
    {
        return DB::transaction(function () use ($productId, $qty) {
            $inventory = $this->newQuery()
                              ->where('product_id', $productId)
                              ->lockForUpdate()
                              ->firstOrFail();

            $release = min($qty, $inventory->reserved_quantity);

            $inventory->decrement('reserved_quantity', $release);
            $inventory->increment('available_quantity', $release);

            InventoryTransaction::create([
                'inventory_id'    => $inventory->id,
                'product_id'      => $productId,
                'tenant_id'       => $inventory->tenant_id,
                'user_id'         => auth()->id(),
                'type'            => InventoryTransaction::TYPE_RELEASED,
                'quantity'        => $release,
                'quantity_before' => $inventory->getOriginal('reserved_quantity'),
                'quantity_after'  => $inventory->reserved_quantity,
            ]);

            return $inventory->fresh();
        });
    }
}

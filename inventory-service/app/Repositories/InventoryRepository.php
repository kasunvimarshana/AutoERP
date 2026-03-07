<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\InventoryRepositoryInterface;
use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PostgreSQL-backed inventory repository.
 *
 * Stock reservation uses a database-level SELECT FOR UPDATE
 * to prevent race conditions in concurrent requests (e.g., two
 * orders trying to reserve the last unit simultaneously).
 */
final class InventoryRepository implements InventoryRepositoryInterface
{
    /** {@inheritDoc} */
    public function listForTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return InventoryItem::with('product')
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function findById(string $id): ?InventoryItem
    {
        return InventoryItem::with('product')->find($id);
    }

    /** {@inheritDoc} */
    public function findByProductId(string $productId, string $tenantId): ?InventoryItem
    {
        return InventoryItem::where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /** {@inheritDoc} */
    public function create(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    /** {@inheritDoc} */
    public function update(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);
        return $item->fresh();
    }

    /** {@inheritDoc} */
    public function delete(InventoryItem $item): void
    {
        $item->delete();
    }

    /**
     * {@inheritDoc}
     *
     * Uses a pessimistic lock (SELECT FOR UPDATE) to prevent
     * overselling when multiple orders arrive simultaneously.
     */
    public function reserveStock(string $productId, string $tenantId, int $quantity): bool
    {
        return DB::transaction(function () use ($productId, $tenantId, $quantity): bool {
            /** @var InventoryItem|null $item */
            $item = InventoryItem::where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (!$item || $item->quantity_available < $quantity) {
                Log::warning('Stock reservation failed – insufficient stock', [
                    'product_id'         => $productId,
                    'requested_quantity' => $quantity,
                    'available'          => $item?->quantity_available ?? 0,
                ]);
                return false;
            }

            $item->decrement('quantity_available', $quantity);
            $item->increment('quantity_reserved', $quantity);

            Log::info('Stock reserved', [
                'product_id'        => $productId,
                'quantity_reserved' => $quantity,
            ]);

            return true;
        });
    }

    /**
     * {@inheritDoc}
     *
     * Compensating transaction – called when a Saga step fails
     * and the inventory reservation must be rolled back.
     */
    public function releaseStock(string $productId, string $tenantId, int $quantity): bool
    {
        return DB::transaction(function () use ($productId, $tenantId, $quantity): bool {
            /** @var InventoryItem|null $item */
            $item = InventoryItem::where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (!$item || $item->quantity_reserved < $quantity) {
                Log::error('Stock release failed – reserved quantity insufficient', [
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                ]);
                return false;
            }

            $item->increment('quantity_available', $quantity);
            $item->decrement('quantity_reserved', $quantity);

            Log::info('Stock released (compensation)', [
                'product_id'       => $productId,
                'quantity_released' => $quantity,
            ]);

            return true;
        });
    }
}

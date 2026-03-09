<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Inventory Repository
 *
 * Handles stock operations with database-level locking to prevent
 * race conditions in distributed environments.
 */
class InventoryRepository extends BaseRepository implements InventoryRepositoryInterface
{
    protected array $filterableColumns = ['tenant_id', 'product_id', 'warehouse_id'];
    protected array $sortableColumns = ['created_at', 'quantity_on_hand'];

    public function __construct(
        InventoryItem $model,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($model);
    }

    public function findByProductAndWarehouse(string $productId, string $warehouseId): ?InventoryItem
    {
        return InventoryItem::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    /**
     * Reserve stock for an order using pessimistic locking (FOR UPDATE).
     * Ensures ACID compliance in concurrent environments.
     */
    public function reserveStock(string $productId, string $warehouseId, int $quantity): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $item = InventoryItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                $this->logger->warning('Inventory item not found for reservation', compact('productId', 'warehouseId'));
                return false;
            }

            $available = $item->quantity_on_hand - $item->quantity_reserved;

            if ($available < $quantity) {
                $this->logger->warning('Insufficient stock for reservation', [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'requested' => $quantity,
                    'available' => $available,
                ]);
                return false;
            }

            $item->increment('quantity_reserved', $quantity);

            $this->logger->info('Stock reserved', compact('productId', 'warehouseId', 'quantity'));
            return true;
        });
    }

    /**
     * Release a stock reservation (Saga compensation action).
     */
    public function releaseReservation(string $productId, string $warehouseId, int $quantity): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $item = InventoryItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return false;
            }

            $release = min($quantity, $item->quantity_reserved);
            $item->decrement('quantity_reserved', $release);

            $this->logger->info('Stock reservation released', compact('productId', 'warehouseId', 'quantity'));
            return true;
        });
    }

    /**
     * Deduct stock from inventory (final commit after payment).
     */
    public function deductStock(string $productId, string $warehouseId, int $quantity): bool
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $item = InventoryItem::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$item || $item->quantity_on_hand < $quantity) {
                return false;
            }

            $item->decrement('quantity_on_hand', $quantity);
            $item->decrement('quantity_reserved', min($quantity, $item->quantity_reserved));

            $this->logger->info('Stock deducted', compact('productId', 'warehouseId', 'quantity'));
            return true;
        });
    }

    /**
     * Get items below reorder threshold.
     */
    public function getLowStockItems(string $tenantId, int $threshold = 10): Collection
    {
        return InventoryItem::where('tenant_id', $tenantId)
            ->whereRaw('quantity_on_hand - quantity_reserved <= reorder_point')
            ->with('product')
            ->get();
    }
}

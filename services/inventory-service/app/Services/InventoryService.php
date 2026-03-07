<?php

namespace App\Services;

use App\Events\InventoryUpdated;
use App\Events\StockDepleted;
use App\Events\StockLow;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService extends BaseService
{
    public function __construct(
        protected InventoryRepositoryInterface   $repository,
        private readonly CrossServiceInventoryService $crossService,
    ) {
        parent::__construct($repository);
    }

    // -------------------------------------------------------------------------
    // Stock operations
    // -------------------------------------------------------------------------

    /**
     * Adjust the stock quantity of an inventory record.
     *
     * @param  string  $inventoryId
     * @param  int     $quantity    Positive to add, negative to remove
     * @param  string  $type        StockMovement type (in/out/adjustment/…)
     * @param  string  $reason      Human-readable reason / notes
     * @param  string|null  $performedBy  User UUID performing the action
     * @return Inventory
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function adjustStock(
        string  $inventoryId,
        int     $quantity,
        string  $type,
        string  $reason,
        ?string $performedBy = null,
    ): Inventory {
        return DB::transaction(function () use ($inventoryId, $quantity, $type, $reason, $performedBy) {
            /** @var Inventory $inventory */
            $inventory = Inventory::lockForUpdate()->findOrFail($inventoryId);

            $previousQty = $inventory->quantity;
            $newQty      = $previousQty + $quantity;

            if ($newQty < 0) {
                throw new \InvalidArgumentException(
                    "Insufficient stock. Available: {$inventory->available_quantity}, Requested: " . abs($quantity)
                );
            }

            $inventory->update([
                'quantity'         => $newQty,
                'last_movement_at' => now(),
            ]);

            StockMovement::create([
                'tenant_id'         => $inventory->tenant_id,
                'inventory_id'      => $inventory->id,
                'product_id'        => $inventory->product_id,
                'warehouse_id'      => $inventory->warehouse_id,
                'type'              => $type,
                'quantity'          => $quantity,
                'previous_quantity' => $previousQty,
                'new_quantity'      => $newQty,
                'notes'             => $reason,
                'performed_by'      => $performedBy,
            ]);

            $inventory->refresh();

            $this->dispatchStockEvents($inventory);

            Log::info('Stock adjusted', [
                'inventory_id' => $inventoryId,
                'type'         => $type,
                'quantity'     => $quantity,
                'new_qty'      => $newQty,
            ]);

            return $inventory;
        });
    }

    /**
     * Transfer stock between two inventory records (ACID transaction).
     *
     * @throws \InvalidArgumentException
     */
    public function transferStock(
        string  $fromInventoryId,
        string  $toInventoryId,
        int     $quantity,
        ?string $notes       = null,
        ?string $performedBy = null,
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Transfer quantity must be positive.');
        }

        if ($fromInventoryId === $toInventoryId) {
            throw new \InvalidArgumentException('Source and destination inventory must differ.');
        }

        return DB::transaction(function () use ($fromInventoryId, $toInventoryId, $quantity, $notes, $performedBy) {
            // Lock both rows in consistent order to avoid deadlocks
            $ids     = collect([$fromInventoryId, $toInventoryId])->sort()->values();
            $locked  = Inventory::lockForUpdate()->whereIn('id', $ids)->get()->keyBy('id');

            $source  = $locked->get($fromInventoryId);
            $dest    = $locked->get($toInventoryId);

            if (! $source || ! $dest) {
                throw new \RuntimeException('One or both inventory records not found.');
            }

            if ($source->available_quantity < $quantity) {
                throw new \InvalidArgumentException(
                    "Insufficient available stock. Available: {$source->available_quantity}, Requested: {$quantity}"
                );
            }

            $sourcePrev = $source->quantity;
            $destPrev   = $dest->quantity;

            $source->update(['quantity' => $sourcePrev - $quantity, 'last_movement_at' => now()]);
            $dest->update(['quantity'   => $destPrev  + $quantity, 'last_movement_at' => now()]);

            $movementData = [
                'tenant_id'         => $source->tenant_id,
                'product_id'        => $source->product_id,
                'type'              => 'transfer',
                'quantity'          => $quantity,
                'notes'             => $notes,
                'performed_by'      => $performedBy,
            ];

            StockMovement::create(array_merge($movementData, [
                'inventory_id'      => $source->id,
                'warehouse_id'      => $source->warehouse_id,
                'previous_quantity' => $sourcePrev,
                'new_quantity'      => $sourcePrev - $quantity,
            ]));

            StockMovement::create(array_merge($movementData, [
                'inventory_id'      => $dest->id,
                'warehouse_id'      => $dest->warehouse_id,
                'quantity'          => $quantity,
                'previous_quantity' => $destPrev,
                'new_quantity'      => $destPrev + $quantity,
            ]));

            $source->refresh();
            $dest->refresh();

            $this->dispatchStockEvents($source);
            $this->dispatchStockEvents($dest);

            return ['source' => $source, 'destination' => $dest];
        });
    }

    /**
     * Reserve stock for an order (increases reserved_quantity).
     *
     * @throws \InvalidArgumentException
     */
    public function reserveStock(
        string  $productId,
        int     $quantity,
        string  $referenceId,
        string  $tenantId,
        ?string $warehouseId = null,
        ?string $performedBy = null,
    ): Inventory {
        return DB::transaction(function () use ($productId, $quantity, $referenceId, $tenantId, $warehouseId, $performedBy) {
            $query = Inventory::lockForUpdate()
                              ->where('tenant_id', $tenantId)
                              ->where('product_id', $productId);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $inventory = $query->firstOrFail();

            if ($inventory->available_quantity < $quantity) {
                throw new \InvalidArgumentException(
                    "Cannot reserve {$quantity} units. Available: {$inventory->available_quantity}"
                );
            }

            $previousQty = $inventory->quantity;
            $inventory->increment('reserved_quantity', $quantity);
            $inventory->update(['last_movement_at' => now()]);

            StockMovement::create([
                'tenant_id'         => $tenantId,
                'inventory_id'      => $inventory->id,
                'product_id'        => $productId,
                'warehouse_id'      => $inventory->warehouse_id,
                'type'              => 'reservation',
                'quantity'          => $quantity,
                'previous_quantity' => $previousQty,
                'new_quantity'      => $inventory->quantity,
                'reference_type'    => 'order',
                'reference_id'      => $referenceId,
                'performed_by'      => $performedBy,
            ]);

            $inventory->refresh();
            InventoryUpdated::dispatch($inventory, 'reservation', $quantity);

            return $inventory;
        });
    }

    /**
     * Release a previously placed stock reservation.
     *
     * @throws \InvalidArgumentException
     */
    public function releaseStock(
        string  $productId,
        int     $quantity,
        string  $referenceId,
        string  $tenantId,
        ?string $warehouseId = null,
        ?string $performedBy = null,
    ): Inventory {
        return DB::transaction(function () use ($productId, $quantity, $referenceId, $tenantId, $warehouseId, $performedBy) {
            $query = Inventory::lockForUpdate()
                              ->where('tenant_id', $tenantId)
                              ->where('product_id', $productId);

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $inventory = $query->firstOrFail();

            if ($inventory->reserved_quantity < $quantity) {
                throw new \InvalidArgumentException(
                    "Cannot release {$quantity} units. Only {$inventory->reserved_quantity} reserved."
                );
            }

            $previousQty = $inventory->quantity;
            $inventory->decrement('reserved_quantity', $quantity);
            $inventory->update(['last_movement_at' => now()]);

            StockMovement::create([
                'tenant_id'         => $tenantId,
                'inventory_id'      => $inventory->id,
                'product_id'        => $productId,
                'warehouse_id'      => $inventory->warehouse_id,
                'type'              => 'release',
                'quantity'          => $quantity,
                'previous_quantity' => $previousQty,
                'new_quantity'      => $inventory->quantity,
                'reference_type'    => 'order',
                'reference_id'      => $referenceId,
                'performed_by'      => $performedBy,
            ]);

            $inventory->refresh();
            InventoryUpdated::dispatch($inventory, 'release', $quantity);

            return $inventory;
        });
    }

    // -------------------------------------------------------------------------
    // Cross-service enrichment
    // -------------------------------------------------------------------------

    /**
     * Return paginated or full inventory list enriched with product data from the Product Service.
     *
     * @return array|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInventoryWithProductDetails(Request $request): mixed
    {
        $tenantId = $request->attributes->get('tenant_id');

        $query = Inventory::query()
                          ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                          ->with('warehouse');

        $result = $this->repository->paginateConditional($query, $request);

        // Extract items whether paginated or plain collection
        $items = $result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
            ? $result->getCollection()
            : $result;

        $enriched = $this->crossService->enrichInventoryWithProducts($items);

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $result->setCollection(collect($enriched));
        } else {
            $result = collect($enriched);
        }

        return $result;
    }

    /**
     * Filter inventory records whose product name matches the given term.
     *
     * @return Collection
     */
    public function filterByProductName(string $productName, ?string $tenantId = null): Collection
    {
        $productIds = $this->crossService->searchProductIdsByName($productName);

        if (empty($productIds)) {
            return new Collection();
        }

        return $this->repository->getByProductIds($productIds, $tenantId);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function dispatchStockEvents(Inventory $inventory): void
    {
        $threshold = (int) config('tenant.low_stock_threshold', 10);

        InventoryUpdated::dispatch($inventory, 'adjustment', 0);

        if ($inventory->available_quantity <= 0) {
            StockDepleted::dispatch($inventory);
        } elseif ($inventory->available_quantity <= $threshold) {
            StockLow::dispatch($inventory, $threshold);
        }
    }
}

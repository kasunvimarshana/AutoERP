<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\InventoryServiceInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Domain\Events\StockMovementRecorded;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;

class InventoryService extends BaseService implements InventoryServiceInterface
{
    /** @var \Illuminate\Database\Eloquent\Model|null */
    private mixed $stockReservationModel = null;

    public function __construct(
        InventoryItemRepositoryInterface $repository,
        private readonly InventoryMovementRepositoryInterface $movementRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->recordMovement($data);
    }

    /**
     * Record a stock movement (inbound or outbound) and update the inventory item.
     */
    public function recordMovement(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $productId   = $data['product_id'];
            $variantId   = $data['variant_id'] ?? null;
            $warehouseId = $data['warehouse_id'];
            $locationId  = $data['location_id'] ?? null;
            $quantity    = (float) ($data['quantity'] ?? 0);
            $unitCost    = (float) ($data['unit_cost'] ?? 0);
            $type        = $data['type'];

            // Determine sign: outbound types subtract
            $outboundTypes = [
                'sale_shipment', 'transfer_out', 'adjustment_out', 'return_out',
            ];
            $isOutbound = in_array($type, $outboundTypes, true);

            /** @var InventoryItemRepositoryInterface $itemRepo */
            $itemRepo = $this->repository;
            $item = $itemRepo->findByProductWarehouse($productId, $warehouseId, $variantId);

            $quantityBefore = $item ? (float) $item->quantity_on_hand : 0.0;

            if ($isOutbound && $quantityBefore < $quantity) {
                throw new InsufficientStockException($productId, $quantity, $quantityBefore);
            }

            $quantityAfter = $isOutbound
                ? $quantityBefore - $quantity
                : $quantityBefore + $quantity;

            // Update or create inventory item
            $newAverageCost = $this->calculateAverageCost(
                $item,
                $isOutbound ? -$quantity : $quantity,
                $unitCost,
                $quantityBefore,
                $quantityAfter,
            );

            $itemData = [
                'product_id'       => $productId,
                'variant_id'       => $variantId,
                'warehouse_id'     => $warehouseId,
                'location_id'      => $locationId,
                'quantity_on_hand' => $quantityAfter,
                'quantity_available' => max(0.0, $quantityAfter - (float) ($item->quantity_reserved ?? 0)),
                'average_cost'     => $newAverageCost,
                'tenant_id'        => $data['tenant_id'] ?? ($item->tenant_id ?? 0),
            ];

            if ($item) {
                $itemRepo->update($item->id, $itemData);
            } else {
                $itemRepo->create($itemData);
            }

            // Record the movement
            $movement = $this->movementRepository->create(array_merge($data, [
                'total_cost'      => $quantity * $unitCost,
                'quantity_before' => $quantityBefore,
                'quantity_after'  => $quantityAfter,
            ]));

            $this->addEvent(new StockMovementRecorded(
                (int) ($data['tenant_id'] ?? 0),
                $movement->id,
                $productId,
                $type,
                $quantity,
            ));
            $this->dispatchEvents();

            return $movement;
        });
    }

    /**
     * Reserve stock for an order or transfer.
     */
    public function reserveStock(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $referenceType,
        string $referenceId,
    ): mixed {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $referenceType, $referenceId) {
            /** @var InventoryItemRepositoryInterface $itemRepo */
            $itemRepo = $this->repository;
            $item = $itemRepo->findByProductWarehouse($productId, $warehouseId);

            if (! $item || (float) $item->quantity_available < $quantity) {
                throw new InsufficientStockException(
                    $productId,
                    $quantity,
                    $item ? (float) $item->quantity_available : 0.0,
                );
            }

            // Update available and reserved quantities
            $itemRepo->update($item->id, [
                'quantity_reserved'  => (float) $item->quantity_reserved + $quantity,
                'quantity_available' => (float) $item->quantity_available - $quantity,
            ]);

            // Create reservation record via the model class directly if available
            return DB::table('stock_reservations')->insertGetId([
                'id'             => \Illuminate\Support\Str::uuid(),
                'tenant_id'      => $item->tenant_id,
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'quantity'       => $quantity,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        });
    }

    /**
     * Release a stock reservation by its ID.
     */
    public function releaseReservation(string $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            $reservation = DB::table('stock_reservations')
                ->where('id', $reservationId)
                ->where('status', 'active')
                ->first();

            if (! $reservation) {
                return;
            }

            DB::table('stock_reservations')
                ->where('id', $reservationId)
                ->update(['status' => 'cancelled', 'updated_at' => now()]);

            /** @var InventoryItemRepositoryInterface $itemRepo */
            $itemRepo = $this->repository;
            $item = $itemRepo->findByProductWarehouse($reservation->product_id, $reservation->warehouse_id);

            if ($item) {
                $itemRepo->update($item->id, [
                    'quantity_reserved'  => max(0.0, (float) $item->quantity_reserved - (float) $reservation->quantity),
                    'quantity_available' => (float) $item->quantity_available + (float) $reservation->quantity,
                ]);
            }
        });
    }

    /**
     * Get stock levels for a product across all warehouses.
     */
    public function getStockLevels(string $productId): Collection
    {
        /** @var InventoryItemRepositoryInterface $itemRepo */
        $itemRepo = $this->repository;

        return $itemRepo->findByProduct($productId);
    }

    /**
     * Recalculate average cost using weighted average method.
     */
    private function calculateAverageCost(
        mixed $item,
        float $quantityChange,
        float $unitCost,
        float $quantityBefore,
        float $quantityAfter,
    ): float {
        if ($quantityAfter <= 0) {
            return 0.0;
        }

        if ($quantityChange > 0 && $unitCost > 0) {
            $oldTotalCost = $quantityBefore * (float) ($item?->average_cost ?? 0);
            $newTotalCost = $quantityChange * $unitCost;
            return ($oldTotalCost + $newTotalCost) / $quantityAfter;
        }

        return (float) ($item?->average_cost ?? 0);
    }
}

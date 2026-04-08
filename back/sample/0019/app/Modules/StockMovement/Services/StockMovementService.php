<?php

namespace App\Modules\StockMovement\Services;

use App\Modules\Allocation\Services\AllocationService;
use App\Modules\Audit\Services\AuditLedgerService;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\SerialNumber;
use App\Modules\Inventory\Models\TrackingLot;
use App\Modules\StockMovement\Events\StockMovementCompleted;
use App\Modules\StockMovement\Events\StockMovementValidated;
use App\Modules\StockMovement\Models\StockMovement;
use App\Modules\StockMovement\Models\StockMovementLine;
use App\Modules\Valuation\Services\InventoryValuationService;
use App\Modules\Warehouse\Services\PutawayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * StockMovementService
 *
 * Orchestrates all physical inventory movements:
 *   - Validates and commits stock movements
 *   - Updates stock levels (on_hand, reserved, in_transit)
 *   - Applies rotation strategy (FIFO/LIFO/FEFO) for source lot selection
 *   - Triggers valuation costing engine
 *   - Writes immutable audit ledger entries
 *   - Manages serial and lot assignments
 *   - Handles partial movements and backorder creation
 *   - Supports double-entry location accounting
 */
class StockMovementService
{
    public function __construct(
        protected InventoryValuationService $valuationService,
        protected AllocationService         $allocationService,
        protected AuditLedgerService        $auditLedger,
        protected PutawayService            $putawayService,
    ) {}

    /**
     * Validate and commit a stock movement to final "done" state.
     * This is the single entry point for all physical movements.
     */
    public function validateMovement(StockMovement $movement, array $options = []): StockMovement
    {
        return DB::transaction(function () use ($movement, $options) {
            $this->assertMovementIsReadyToValidate($movement);

            $effectiveDate = $options['effective_date'] ?? now();
            $movement->effective_date = $effectiveDate;

            // Process each line
            $partialLines   = 0;
            $completedLines = 0;

            foreach ($movement->lines as $line) {
                if ($line->done_qty <= 0) {
                    continue;
                }

                // 1. Auto-select lot/serial if not specified (apply rotation strategy)
                if (! $line->lot_id && $this->requiresLotTracking($line)) {
                    $line = $this->autoAssignLot($line, $movement);
                }

                // 2. Validate stock availability at source location
                if ($movement->operationType->category !== 'incoming') {
                    $this->assertSufficientStock($line);
                }

                // 3. Perform the actual stock level mutation (double-entry)
                $cogsInfo = $this->applyStockMove($line, $movement, $effectiveDate);

                // 4. Update serial numbers if tracked
                if ($line->serial_number_id || $line->product->track_serial_numbers) {
                    $this->updateSerialNumbers($line, $movement);
                }

                // 5. Write audit ledger (immutable)
                $this->auditLedger->writeMovement($line, $movement, $cogsInfo, $effectiveDate);

                // 6. Release reservations if this fulfills a sales order
                if ($movement->source_document_type === 'App\Modules\Sales\Models\SalesOrderLine') {
                    $this->allocationService->releaseReservation(
                        $movement->source_document_type,
                        $movement->source_document_id,
                        $line->done_qty,
                        $line->product_id,
                        $line->lot_id
                    );
                }

                $line->status = 'done';
                $line->save();

                $line->done_qty < $line->demand_qty ? $partialLines++ : $completedLines++;
            }

            // Handle backorders for partially moved lines
            if ($partialLines > 0 && $movement->operationType->create_backorder) {
                $backorder = $this->createBackorder($movement);
                $movement->backorder_created = $backorder->id;
            }

            $movement->status       = 'done';
            $movement->completed_at = now();
            $movement->save();

            Event::dispatch(new StockMovementCompleted($movement));

            return $movement;
        });
    }

    /**
     * Apply the double-entry stock level update for a single movement line.
     * SOURCE location loses stock; DESTINATION location gains stock.
     */
    protected function applyStockMove(
        StockMovementLine $line,
        StockMovement $movement,
        $effectiveDate
    ): array {
        $cogsInfo = ['unit_cost' => 0, 'total_cogs' => 0];

        $sourceLocationId = $line->source_location_id ?? $movement->source_location_id;
        $destLocationId   = $line->destination_location_id ?? $movement->destination_location_id;

        // ── DEDUCT from source ───────────────────────────────────────────────
        if ($sourceLocationId) {
            $cogsInfo = $this->valuationService->recordIssue([
                'tenant_id'       => $movement->tenant_id,
                'product_id'      => $line->product_id,
                'variant_id'      => $line->variant_id,
                'warehouse_id'    => $movement->warehouse_id ?? $this->resolveWarehouse($sourceLocationId),
                'lot_id'          => $line->lot_id,
                'qty'             => $line->done_qty,
                'uom_id'          => $line->uom_id,
                'reference_type'  => StockMovementLine::class,
                'reference_id'    => $line->id,
                'transaction_date' => $effectiveDate,
            ]);

            $this->decrementStockLevel(
                $line->product_id, $line->variant_id,
                $this->resolveWarehouse($sourceLocationId), $sourceLocationId,
                $line->lot_id, $line->done_qty, $line->uom_id,
                $cogsInfo['unit_cost']
            );
        }

        // ── ADD to destination ───────────────────────────────────────────────
        if ($destLocationId) {
            $unitCost = $cogsInfo['unit_cost'] > 0
                ? $cogsInfo['unit_cost']
                : ($line->unit_cost > 0 ? $line->unit_cost : 0);

            // For incoming movements, create a new receipt layer
            if ($movement->operationType->category === 'incoming') {
                $this->valuationService->recordReceipt([
                    'tenant_id'      => $movement->tenant_id,
                    'product_id'     => $line->product_id,
                    'variant_id'     => $line->variant_id,
                    'warehouse_id'   => $this->resolveWarehouse($destLocationId),
                    'lot_id'         => $line->lot_id,
                    'qty'            => $line->done_qty,
                    'unit_cost'      => $unitCost,
                    'uom_id'         => $line->uom_id,
                    'reference_type' => StockMovementLine::class,
                    'reference_id'   => $line->id,
                    'layer_date'     => $effectiveDate,
                ]);
            }

            $this->incrementStockLevel(
                $line->product_id, $line->variant_id,
                $this->resolveWarehouse($destLocationId), $destLocationId,
                $line->lot_id, $line->done_qty, $line->uom_id,
                $unitCost
            );
        }

        // Update line costing info
        $line->unit_cost   = $cogsInfo['unit_cost'];
        $line->total_cost  = $cogsInfo['total_cogs'];
        $line->save();

        return $cogsInfo;
    }

    /**
     * Auto-assign lot(s) to a movement line based on the warehouse rotation strategy.
     */
    protected function autoAssignLot(StockMovementLine $line, StockMovement $movement): StockMovementLine
    {
        $settings = \App\Modules\Inventory\Models\InventorySetting::forWarehouse($movement->warehouse_id)->first();
        $strategy = $settings?->stock_rotation_strategy ?? 'fifo';

        $query = \App\Modules\Inventory\Models\StockLevel::where([
            'product_id'   => $line->product_id,
            'variant_id'   => $line->variant_id,
            'warehouse_id' => $movement->warehouse_id,
        ])->where('qty_available', '>', 0)
          ->whereNotNull('lot_id');

        switch ($strategy) {
            case 'fefo':
                $query->join('tracking_lots', 'tracking_lots.id', '=', 'stock_levels.lot_id')
                      ->orderBy('tracking_lots.expiry_date', 'asc');
                break;
            case 'lefo':
                $query->join('tracking_lots', 'tracking_lots.id', '=', 'stock_levels.lot_id')
                      ->orderBy('tracking_lots.expiry_date', 'desc');
                break;
            case 'lifo':
                $query->orderBy('first_in_date', 'desc');
                break;
            case 'fmfo':
                $query->join('tracking_lots', 'tracking_lots.id', '=', 'stock_levels.lot_id')
                      ->orderBy('tracking_lots.manufacture_date', 'asc');
                break;
            default: // FIFO
                $query->orderBy('first_in_date', 'asc');
        }

        $stockLevel = $query->first();
        if ($stockLevel) {
            $line->lot_id = $stockLevel->lot_id;
            $line->source_location_id = $line->source_location_id ?? $stockLevel->location_id;
            $line->save();
        }

        return $line;
    }

    /**
     * Increment stock level (destination).
     */
    protected function incrementStockLevel(
        int $productId, ?int $variantId,
        int $warehouseId, ?int $locationId,
        ?int $lotId, float $qty, int $uomId, float $unitCost
    ): void {
        StockLevel::updateOrCreate(
            compact('productId', 'variantId', 'warehouseId', 'locationId', 'lotId', 'uomId') + [
                'product_id' => $productId, 'variant_id' => $variantId,
                'warehouse_id' => $warehouseId, 'location_id' => $locationId,
                'lot_id' => $lotId, 'uom_id' => $uomId,
            ],
            []
        )->increment('qty_on_hand', $qty, [
            'qty_available' => DB::raw("qty_available + $qty"),
            'total_value'   => DB::raw("(qty_on_hand + $qty) * unit_cost"),
            'first_in_date' => DB::raw("COALESCE(first_in_date, NOW())"),
            'last_move_date' => now(),
        ]);
    }

    /**
     * Decrement stock level (source).
     */
    protected function decrementStockLevel(
        int $productId, ?int $variantId,
        int $warehouseId, ?int $locationId,
        ?int $lotId, float $qty, int $uomId, float $unitCost
    ): void {
        $level = StockLevel::where([
            'product_id'   => $productId,
            'variant_id'   => $variantId,
            'warehouse_id' => $warehouseId,
            'location_id'  => $locationId,
            'lot_id'       => $lotId,
        ])->lockForUpdate()->first();

        if ($level) {
            $level->decrement('qty_on_hand', $qty);
            $level->decrement('qty_available', $qty);
            $level->last_move_date = now();
            $level->total_value    = $level->qty_on_hand * $level->unit_cost;
            $level->save();
        }
    }

    protected function updateSerialNumbers(StockMovementLine $line, StockMovement $movement): void
    {
        $category = $movement->operationType->category;
        $newStatus = match ($category) {
            'outgoing'     => 'sold',
            'incoming'     => 'in_stock',
            'internal'     => 'in_stock',
            'returns'      => 'returned',
            'scrap'        => 'scrapped',
            default        => 'in_stock',
        };

        foreach ($line->serials as $serial) {
            $serial->update([
                'status'      => $newStatus,
                'warehouse_id' => $this->resolveWarehouse($line->destination_location_id ?? $movement->destination_location_id),
                'location_id' => $line->destination_location_id ?? $movement->destination_location_id,
            ]);
        }
    }

    protected function createBackorder(StockMovement $movement): StockMovement
    {
        $backorder = $movement->replicate(['id', 'reference_number', 'status', 'completed_at', 'backorder_of_id']);
        $backorder->reference_number = $this->generateReference($movement->operationType);
        $backorder->status           = 'confirmed';
        $backorder->backorder_of_id  = $movement->id;
        $backorder->save();

        foreach ($movement->lines as $line) {
            $remaining = bcsub($line->demand_qty, $line->done_qty, 6);
            if ($remaining > 0) {
                $boLine = $line->replicate(['id', 'movement_id', 'done_qty', 'status']);
                $boLine->movement_id     = $backorder->id;
                $boLine->demand_qty      = $remaining;
                $boLine->done_qty        = 0;
                $boLine->is_backorder    = true;
                $boLine->backorder_line_id = $line->id;
                $boLine->status          = 'confirmed';
                $boLine->save();
            }
        }

        return $backorder;
    }

    protected function assertMovementIsReadyToValidate(StockMovement $movement): void
    {
        if (! in_array($movement->status, ['confirmed', 'ready', 'in_progress'])) {
            throw new \DomainException("Movement [{$movement->reference_number}] cannot be validated from status [{$movement->status}].");
        }
    }

    protected function assertSufficientStock(StockMovementLine $line): void
    {
        $settings = \App\Modules\Inventory\Models\InventorySetting::first();
        if ($settings?->allow_negative_stock) return;

        $available = StockLevel::where([
            'product_id'  => $line->product_id,
            'variant_id'  => $line->variant_id,
            'warehouse_id' => $line->movement->warehouse_id,
        ])->when($line->lot_id, fn($q) => $q->where('lot_id', $line->lot_id))
          ->value('qty_available') ?? 0;

        if ($available < $line->done_qty) {
            throw new \DomainException(
                "Insufficient stock for product [{$line->product_id}]. Available: {$available}, Required: {$line->done_qty}."
            );
        }
    }

    protected function requiresLotTracking(StockMovementLine $line): bool
    {
        return $line->product->track_batches || $line->product->track_lots;
    }

    protected function resolveWarehouse(int $locationId): int
    {
        return \App\Modules\Warehouse\Models\WarehouseLocation::find($locationId)?->warehouse_id ?? 0;
    }

    protected function generateReference($operationType): string
    {
        $prefix = $operationType->sequence_prefix ?? 'MOV';
        $seq    = str_pad($operationType->next_sequence++, 5, '0', STR_PAD_LEFT);
        $operationType->save();
        return "{$prefix}/{$seq}";
    }
}

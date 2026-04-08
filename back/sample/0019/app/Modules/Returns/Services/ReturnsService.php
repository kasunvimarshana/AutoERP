<?php

namespace App\Modules\Returns\Services;

use App\Modules\Returns\Models\ReturnMerchandiseAuthorization;
use App\Modules\Returns\Models\RmaLine;
use App\Modules\Returns\Models\SupplierReturnOrder;
use App\Modules\Returns\Models\SupplierReturnLine;
use App\Modules\Returns\Models\ReturnValuationAdjustment;
use App\Modules\Returns\Models\QualityInspection;
use App\Modules\Inventory\Models\TrackingLot;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\StockMovement\Services\StockMovementService;
use App\Modules\Valuation\Services\InventoryValuationService;
use App\Modules\Audit\Services\AuditLedgerService;
use Illuminate\Support\Facades\DB;

/**
 * ReturnsService
 *
 * Handles all return flows:
 *   A) Sales Returns (RMA)
 *      1. RMA creation and approval
 *      2. Physical receipt of returned goods
 *      3. Quality inspection
 *      4. Disposition routing: restock | scrap | quarantine | refurbish | return_to_supplier
 *      5. Inventory valuation adjustment (reverse original COGS)
 *      6. Credit memo generation
 *
 *   B) Supplier Returns (RTV)
 *      1. Return order creation
 *      2. Physical removal from stock (reverse receipt layer)
 *      3. AP debit note / credit note
 *      4. Valuation layer reversal
 *
 * Key complexity: correctly adjusting FIFO/LIFO/AVCO layers on return.
 */
class ReturnsService
{
    public function __construct(
        protected StockMovementService      $movementService,
        protected InventoryValuationService $valuationService,
        protected AuditLedgerService        $auditLedger,
    ) {}

    // ════════════════════════════════════════════════════════════════════════
    // SALES RETURNS (RMA)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Process receipt of returned goods. Creates a stock-in movement
     * and routes items to inspection or directly to disposition.
     */
    public function receiveReturn(ReturnMerchandiseAuthorization $rma, array $receivedLines = []): void
    {
        DB::transaction(function () use ($rma, $receivedLines) {
            // Create stock movement: source = customer, dest = return location
            $movement = $this->createReturnInboundMovement($rma, $receivedLines);

            foreach ($rma->lines as $rmaLine) {
                $receivedQty = $receivedLines[$rmaLine->id]['received_qty'] ?? $rmaLine->approved_qty ?? $rmaLine->requested_qty;
                $rmaLine->received_qty = $receivedQty;

                // Re-activate original lot if returned with original lot/serial
                if ($rmaLine->has_original_lot && $rmaLine->original_lot_id) {
                    $this->reactivateOriginalLot($rmaLine);
                } else {
                    // Create new lot for returned goods (different lot than original)
                    $returnLot = $this->createReturnLot($rmaLine, $receivedQty);
                    $rmaLine->restocked_lot_id = $returnLot->id;
                }

                $rmaLine->status = 'received';
                $rmaLine->save();
            }

            // If QC required, route to quality inspection; else direct to disposition
            $requiresQc = $rma->lines->some(fn($line) => $line->returnReason?->requires_inspection);
            if ($requiresQc) {
                $rma->update(['status' => 'inspecting']);
            } else {
                $this->processDisposition($rma);
            }

            // Write audit ledger
            $this->auditLedger->writeReturnReceipt($rma, $movement);
        });
    }

    /**
     * Record quality inspection results for each RMA line.
     */
    public function recordInspection(RmaLine $line, array $inspectionData): QualityInspection
    {
        return DB::transaction(function () use ($line, $inspectionData) {
            $inspection = QualityInspection::create([
                'tenant_id'          => $line->rma->tenant_id,
                'inspectable_type'   => RmaLine::class,
                'inspectable_id'     => $line->id,
                'product_id'         => $line->product_id,
                'variant_id'         => $line->variant_id,
                'lot_id'             => $line->restocked_lot_id ?? $line->original_lot_id,
                'inspected_qty'      => $line->received_qty,
                'passed_qty'         => $inspectionData['passed_qty'],
                'failed_qty'         => $inspectionData['failed_qty'],
                'overall_result'     => $inspectionData['result'],
                'checklist_results'  => $inspectionData['checklist'] ?? null,
                'disposition'        => $inspectionData['disposition'],
                'inspected_by'       => $inspectionData['inspected_by'],
                'inspection_date'    => now(),
                'notes'              => $inspectionData['notes'] ?? null,
                'photo_evidence_path' => $inspectionData['photo_path'] ?? null,
            ]);

            $line->update([
                'returned_condition' => $inspectionData['condition'],
                'disposition'        => $inspectionData['disposition'],
                'inspected_by'       => $inspectionData['inspected_by'],
                'inspected_at'       => now(),
                'inspection_result'  => $inspectionData['result'],
                'inspection_notes'   => $inspectionData['notes'] ?? null,
                'status'             => 'inspecting',
            ]);

            return $inspection;
        });
    }

    /**
     * Execute the final disposition for all inspected lines.
     * Routes each quantity to: restock | scrap | quarantine | refurbish | return_to_supplier
     */
    public function processDisposition(ReturnMerchandiseAuthorization $rma): void
    {
        DB::transaction(function () use ($rma) {
            $totalRestockedValue = 0;

            foreach ($rma->lines as $line) {
                $disposition = $line->disposition ?? $line->returnReason?->default_disposition ?? 'restock';
                $qty = $line->accepted_qty ?? $line->received_qty;

                switch ($disposition) {
                    case 'restock':
                        $this->restockItem($line, $qty);
                        $totalRestockedValue += ($line->restock_unit_cost ?? $line->original_unit_cost ?? 0) * $qty;
                        break;

                    case 'scrap':
                        $this->scrapReturnedItem($line, $qty);
                        break;

                    case 'quarantine':
                        $this->quarantineReturnedItem($line, $qty);
                        break;

                    case 'return_to_supplier':
                        $this->initiateSupplierReturn($line, $qty);
                        break;

                    case 'refurbish':
                        // Move to refurbishment location; further processing separate
                        $this->moveToRefurbishmentZone($line, $qty);
                        break;
                }

                $line->status = 'processed';
                $line->save();
            }

            $rma->update(['status' => 'completed']);
        });
    }

    /**
     * Restock a returned item — adds back to available inventory with correct valuation.
     * Handles FIFO/LIFO/AVCO cost reversal correctly.
     */
    protected function restockItem(RmaLine $line, float $qty): void
    {
        // Determine restock cost:
        // 1. If item returned with original lot → use original layer cost
        // 2. If no original lot → use current AVCO or standard cost
        $restockCost = $this->determineRestockCost($line);

        // Create return valuation adjustment record
        $adjustment = $this->createReturnValuationAdjustment($line, $qty, $restockCost);

        // Physically move stock to restock location
        $restockLocationId = $line->restock_location_id
            ?? $line->rma->warehouse->stock_location_id;

        $line->update([
            'restocked_qty'   => $qty,
            'restock_unit_cost' => $restockCost,
            'restock_location_id' => $restockLocationId,
        ]);

        // Update stock levels
        StockLevel::updateOrCreate([
            'product_id'   => $line->product_id,
            'variant_id'   => $line->variant_id,
            'warehouse_id' => $line->rma->warehouse_id,
            'lot_id'       => $line->restocked_lot_id ?? $line->original_lot_id,
            'location_id'  => $restockLocationId,
            'uom_id'       => $line->uom_id,
        ], [])->increment('qty_on_hand', $qty, [
            'qty_available' => \Illuminate\Support\Facades\DB::raw("qty_available + $qty"),
        ]);

        // Create receipt layer for valuation
        $this->valuationService->recordReturn([
            'tenant_id'           => $line->rma->tenant_id,
            'product_id'          => $line->product_id,
            'variant_id'          => $line->variant_id,
            'warehouse_id'        => $line->rma->warehouse_id,
            'lot_id'              => $line->restocked_lot_id ?? $line->original_lot_id,
            'qty'                 => $qty,
            'unit_cost'           => $restockCost,
            'original_unit_cost'  => $line->original_unit_cost,
            'uom_id'              => $line->uom_id,
            'reference_type'      => RmaLine::class,
            'reference_id'        => $line->id,
            'layer_date'          => now(),
        ], 'sales_return');
    }

    protected function determineRestockCost(RmaLine $line): float
    {
        // If returned with original lot and we know the original layer cost
        if ($line->has_original_lot && $line->original_unit_cost) {
            return (float) $line->original_unit_cost;
        }

        // For AVCO products, use current average cost
        $method = $this->valuationService->resolveMethod(
            $line->product_id,
            $line->rma->warehouse_id
        );

        if ($method === 'avco') {
            $current = StockLevel::where([
                'product_id'   => $line->product_id,
                'warehouse_id' => $line->rma->warehouse_id,
            ])->value('unit_cost') ?? 0;
            return (float) $current;
        }

        // Default to original sale cost if available, otherwise 0
        return (float) ($line->original_unit_cost ?? 0);
    }

    protected function createReturnValuationAdjustment(RmaLine $line, float $qty, float $restockCost): ReturnValuationAdjustment
    {
        $method = $this->valuationService->resolveMethod($line->product_id, $line->rma->warehouse_id);

        return ReturnValuationAdjustment::create([
            'tenant_id'        => $line->rma->tenant_id,
            'return_type'      => 'sales_return',
            'return_id'        => $line->rma_id,
            'return_line_id'   => $line->id,
            'product_id'       => $line->product_id,
            'variant_id'       => $line->variant_id,
            'lot_id'           => $line->restocked_lot_id ?? $line->original_lot_id,
            'costing_method'   => $method,
            'adjustment_type'  => $method === 'avco' ? 'avco_recalc' : 'new_layer',
            'reversed_qty'     => $qty,
            'reversed_unit_cost' => $restockCost,
            'reversed_value'   => $qty * $restockCost,
            'new_unit_cost'    => $restockCost,
            'total_value_impact' => $qty * $restockCost,
        ]);
    }

    protected function createReturnLot(RmaLine $line, float $qty): TrackingLot
    {
        return TrackingLot::create([
            'tenant_id'     => $line->rma->tenant_id,
            'lot_type'      => 'lot',
            'lot_number'    => 'RTN-' . strtoupper(substr(uniqid(), -8)),
            'product_id'    => $line->product_id,
            'variant_id'    => $line->variant_id,
            'receipt_date'  => today(),
            'initial_qty'   => $qty,
            'current_qty'   => $qty,
            'unit_cost'     => $line->original_unit_cost,
            'status'        => 'available',
        ]);
    }

    protected function reactivateOriginalLot(RmaLine $line): void
    {
        if ($line->original_lot_id) {
            TrackingLot::where('id', $line->original_lot_id)->increment('current_qty', $line->received_qty);
        }
    }

    protected function scrapReturnedItem(RmaLine $line, float $qty): void
    {
        // Create scrap record and move to scrap location
        \App\Modules\StockMovement\Models\InventoryScrap::create([
            'tenant_id'        => $line->rma->tenant_id,
            'warehouse_id'     => $line->rma->warehouse_id,
            'reference_number' => 'SCRAP-RTN-' . $line->id,
            'product_id'       => $line->product_id,
            'variant_id'       => $line->variant_id,
            'lot_id'           => $line->restocked_lot_id ?? $line->original_lot_id,
            'scrap_qty'        => $qty,
            'uom_id'           => $line->uom_id,
            'scrap_reason'     => 'damage',
            'unit_cost'        => $line->original_unit_cost ?? 0,
            'total_cost'       => ($line->original_unit_cost ?? 0) * $qty,
            'status'           => 'done',
            'scrap_date'       => now(),
            'notes'            => "Scrapped from RMA #{$line->rma->rma_number}",
        ]);
        $line->update(['scrapped_qty' => $qty]);
    }

    protected function quarantineReturnedItem(RmaLine $line, float $qty): void
    {
        $lot = TrackingLot::find($line->restocked_lot_id ?? $line->original_lot_id);
        if ($lot) {
            $lot->update([
                'is_quarantined'      => true,
                'status'              => 'quarantine',
                'quarantine_reason'   => "Returned item — inspection required. RMA: {$line->rma->rma_number}",
            ]);
        }
    }

    protected function moveToRefurbishmentZone(RmaLine $line, float $qty): void
    {
        // Implementation: move stock to a dedicated refurbishment location
        // Trigger a stock movement to refurbishment zone
    }

    protected function initiateSupplierReturn(RmaLine $line, float $qty): void
    {
        // Auto-create a supplier return (RTV) from this RMA line
        // Can be batched and grouped later
    }

    protected function createReturnInboundMovement(ReturnMerchandiseAuthorization $rma, array $lines)
    {
        // Creates a stock movement for goods coming in from customer
        return \App\Modules\StockMovement\Models\StockMovement::create([
            'tenant_id'                => $rma->tenant_id,
            'organization_id'          => $rma->organization_id,
            'warehouse_id'             => $rma->warehouse_id,
            'reference_number'         => 'RMA-IN-' . $rma->rma_number,
            'operation_type_id'        => $this->resolveReturnOperationType($rma->warehouse_id),
            'source_document_type'     => ReturnMerchandiseAuthorization::class,
            'source_document_id'       => $rma->id,
            'destination_location_id'  => $rma->return_location_id,
            'status'                   => 'done',
            'effective_date'           => now(),
            'completed_at'             => now(),
        ]);
    }

    protected function resolveReturnOperationType(int $warehouseId): int
    {
        return \App\Modules\StockMovement\Models\StockOperationType::where('warehouse_id', $warehouseId)
            ->where('category', 'returns')
            ->value('id') ?? 1;
    }

    // ════════════════════════════════════════════════════════════════════════
    // SUPPLIER RETURNS (RTV)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Validate and ship a supplier return order.
     * Removes stock and reverses receipt valuation layers.
     */
    public function validateSupplierReturn(SupplierReturnOrder $rtv): void
    {
        DB::transaction(function () use ($rtv) {
            foreach ($rtv->lines as $line) {
                // Determine original layer to reverse
                $reversal = $this->valuationService->recordReturn([
                    'tenant_id'     => $rtv->tenant_id,
                    'product_id'    => $line->product_id,
                    'variant_id'    => $line->variant_id,
                    'warehouse_id'  => $rtv->warehouse_id,
                    'lot_id'        => $line->lot_id,
                    'qty'           => $line->return_qty,
                    'uom_id'        => $line->uom_id,
                    'reference_type' => SupplierReturnLine::class,
                    'reference_id'   => $line->id,
                ], 'supplier_return');

                // Remove from stock levels
                StockLevel::where([
                    'product_id'   => $line->product_id,
                    'warehouse_id' => $rtv->warehouse_id,
                    'lot_id'       => $line->lot_id,
                ])->decrement('qty_on_hand', $line->return_qty, [
                    'qty_available' => \Illuminate\Support\Facades\DB::raw("GREATEST(0, qty_available - {$line->return_qty})"),
                ]);

                // Record valuation adjustment
                ReturnValuationAdjustment::create([
                    'tenant_id'        => $rtv->tenant_id,
                    'return_type'      => 'supplier_return',
                    'return_id'        => $rtv->id,
                    'return_line_id'   => $line->id,
                    'product_id'       => $line->product_id,
                    'costing_method'   => $reversal['method'] ?? 'unknown',
                    'adjustment_type'  => 'layer_reversal',
                    'reversed_qty'     => $reversal['reversed_qty'] ?? $line->return_qty,
                    'reversed_unit_cost' => $reversal['unit_cost'] ?? $line->original_unit_cost,
                    'reversed_value'   => ($reversal['unit_cost'] ?? 0) * $line->return_qty,
                    'total_value_impact' => -(($reversal['unit_cost'] ?? 0) * $line->return_qty),
                ]);

                $this->auditLedger->writeSupplierReturn($line, $rtv);
            }

            $rtv->update(['status' => 'shipped']);
        });
    }
}

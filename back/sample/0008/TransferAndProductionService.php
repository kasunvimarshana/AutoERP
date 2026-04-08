<?php

namespace App\Services;

use App\Models\{
    StockTransfer, StockTransferLine, ProductionOrder, ProductionOrderLine,
    BillOfMaterial, BomComponent, Batch, Lot
};
use App\Services\Inventory\InventoryEngine;
use Illuminate\Support\Facades\DB;

/**
 * TransferService
 *
 * Manages stock movements between warehouses or locations.
 *
 * Transfer Types:
 *   warehouse     — move stock from one warehouse to another
 *   location      — move within same warehouse (bin to bin)
 *   replenishment — planned restocking from primary to secondary
 *   consignment   — move to consignment holding
 *   return_to_vendor — send back to supplier
 *
 * Lifecycle: draft → approved → in_transit → partially_received → received
 */
class TransferService
{
    public function __construct(
        private InventoryEngine         $inventory,
        private DocumentSequenceService $sequences,
    ) {}

    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create(array_merge($data, [
                'transfer_number' => $this->sequences->next($data['organization_id'], 'transfer'),
                'status'          => 'draft',
                'created_by'      => auth()->id(),
            ]));

            foreach ($data['lines'] as $line) {
                StockTransferLine::create(array_merge($line, [
                    'stock_transfer_id' => $transfer->id,
                    'status'            => 'pending',
                ]));
            }

            return $transfer->fresh(['lines']);
        });
    }

    public function approve(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->status !== 'draft') {
            throw new \RuntimeException("Only draft transfers can be approved.");
        }

        $transfer->update(['status' => 'approved', 'approved_by' => auth()->id()]);
        return $transfer;
    }

    public function ship(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer) {
            if (!in_array($transfer->status, ['approved'])) {
                throw new \RuntimeException("Transfer must be approved before shipping.");
            }

            foreach ($transfer->lines as $line) {
                // Issue stock from source warehouse
                $this->inventory->issueStock([
                    'organization_id'    => $transfer->organization_id,
                    'product_id'         => $line->product_id,
                    'product_variant_id' => $line->product_variant_id,
                    'warehouse_id'       => $transfer->source_warehouse_id,
                    'storage_location_id'=> $transfer->source_location_id,
                    'lot_id'             => $line->lot_id,
                    'batch_id'           => $line->batch_id,
                    'serial_number_id'   => $line->serial_number_id,
                    'quantity'           => $line->quantity_requested,
                    'movement_type'      => 'transfer_out',
                    'source_document_type' => 'stock_transfer',
                    'source_document_id'   => $transfer->id,
                    'movement_date'        => now(),
                ]);

                $line->update([
                    'quantity_shipped' => $line->quantity_requested,
                    'status'           => 'shipped',
                ]);
            }

            $transfer->update([
                'status'     => 'in_transit',
                'shipped_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }

    public function receive(StockTransfer $transfer, array $receipts): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receipts) {
            foreach ($receipts as $receipt) {
                $line = StockTransferLine::findOrFail($receipt['line_id']);
                $qtyReceived = $receipt['quantity_received'];
                $discrepancy = $line->quantity_shipped - $qtyReceived;

                $this->inventory->receiveStock([
                    'organization_id'     => $transfer->organization_id,
                    'product_id'          => $line->product_id,
                    'product_variant_id'  => $line->product_variant_id,
                    'warehouse_id'        => $transfer->destination_warehouse_id,
                    'storage_location_id' => $transfer->destination_location_id,
                    'lot_id'              => $line->lot_id,
                    'batch_id'            => $line->batch_id,
                    'serial_number_id'    => $line->serial_number_id,
                    'quantity'            => $qtyReceived,
                    'unit_cost'           => $line->unit_cost,
                    'movement_type'       => 'transfer_in',
                    'source_document_type' => 'stock_transfer',
                    'source_document_id'   => $transfer->id,
                    'movement_date'        => now(),
                ]);

                $line->update([
                    'quantity_received'   => $qtyReceived,
                    'quantity_discrepancy'=> $discrepancy,
                    'discrepancy_reason'  => $receipt['discrepancy_reason'] ?? null,
                    'status'              => $discrepancy != 0 ? 'discrepancy' : 'received',
                ]);
            }

            $allReceived = $transfer->lines()->where('status', '!=', 'received')->doesntExist();
            $transfer->update([
                'status'      => $allReceived ? 'received' : 'partially_received',
                'received_at' => now(),
            ]);

            return $transfer->fresh();
        });
    }
}


/**
 * ProductionOrderService
 *
 * Manages manufacturing execution:
 *   Draft → Planned → Released → In Progress → Completed
 *
 * On completion:
 *  - Consumes raw materials from stock
 *  - Produces finished goods into stock
 *  - Creates output batch/lot if product is tracked
 *  - Records cost variance vs planned
 */
class ProductionOrderService
{
    public function __construct(
        private InventoryEngine         $inventory,
        private DocumentSequenceService $sequences,
    ) {}

    public function create(array $data): ProductionOrder
    {
        return DB::transaction(function () use ($data) {
            $bom = isset($data['bom_id'])
                ? BillOfMaterial::findOrFail($data['bom_id'])
                : null;

            $order = ProductionOrder::create(array_merge($data, [
                'production_number' => $this->sequences->next($data['organization_id'], 'production'),
                'status'            => 'draft',
                'created_by'        => auth()->id(),
            ]));

            // Populate lines from BOM if provided
            if ($bom) {
                $this->populateLinesFromBom($order, $bom, $data['quantity_planned']);
            } elseif (!empty($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    ProductionOrderLine::create(array_merge($line, [
                        'production_order_id' => $order->id,
                        'status'              => 'pending',
                    ]));
                }
            }

            // Calculate planned cost
            $plannedCost = $order->lines->sum(fn ($l) => $l->quantity_required * ($l->unit_cost ?? 0));
            $order->update(['planned_cost' => $plannedCost]);

            return $order->fresh(['lines']);
        });
    }

    public function release(ProductionOrder $order): ProductionOrder
    {
        if (!in_array($order->status, ['draft', 'planned'])) {
            throw new \RuntimeException("Cannot release a production order in status: {$order->status}");
        }

        // Check component availability
        foreach ($order->lines as $line) {
            $available = \App\Models\StockPosition::where('product_id', $line->product_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->sum('qty_available');

            if ($available < $line->quantity_required) {
                throw new \RuntimeException(
                    "Insufficient stock for component #{$line->product_id}. "
                    . "Required: {$line->quantity_required}, Available: {$available}"
                );
            }
        }

        $order->update(['status' => 'released']);
        return $order;
    }

    public function issueComponents(ProductionOrder $order): ProductionOrder
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->lines as $line) {
                $qty = $line->quantity_required - $line->quantity_issued;
                if ($qty <= 0) continue;

                $this->inventory->issueStock([
                    'organization_id'    => $order->organization_id,
                    'product_id'         => $line->product_id,
                    'product_variant_id' => $line->product_variant_id,
                    'warehouse_id'       => $order->warehouse_id,
                    'lot_id'             => $line->lot_id,
                    'batch_id'           => $line->batch_id,
                    'quantity'           => $qty,
                    'movement_type'      => 'production_consume',
                    'source_document_type' => 'production_order',
                    'source_document_id'   => $order->id,
                    'movement_date'        => now(),
                ]);

                $line->increment('quantity_issued', $qty);
                $line->update(['status' => 'issued']);
            }

            $order->update([
                'status'          => 'in_progress',
                'actual_start_at' => $order->actual_start_at ?? now(),
            ]);

            return $order->fresh();
        });
    }

    public function complete(ProductionOrder $order, float $quantityProduced, float $quantityScrapped = 0, array $outputData = []): ProductionOrder
    {
        return DB::transaction(function () use ($order, $quantityProduced, $quantityScrapped, $outputData) {
            // Create output batch/lot for the finished good
            $batchId = null;
            $lotId   = null;

            $product = \App\Models\Product::find($order->product_id);
            if ($product?->track_batches) {
                $batch = Batch::create([
                    'organization_id' => $order->organization_id,
                    'product_id'      => $order->product_id,
                    'product_variant_id' => $order->product_variant_id,
                    'batch_number'    => $outputData['batch_number'] ?? ($order->production_number . '-OUT'),
                    'status'          => 'active',
                    'manufacture_date'=> $outputData['manufacture_date'] ?? today(),
                    'expiry_date'     => $outputData['expiry_date'] ?? null,
                    'qc_status'       => 'pending',
                ]);
                $batchId = $batch->id;
            }

            // Produce finished goods into stock
            if ($quantityProduced > 0) {
                $unitCost = $order->planned_cost > 0
                    ? ($order->planned_cost / $order->quantity_planned)
                    : ($order->product?->standard_cost ?? 0);

                $this->inventory->receiveStock([
                    'organization_id'    => $order->organization_id,
                    'product_id'         => $order->product_id,
                    'product_variant_id' => $order->product_variant_id,
                    'warehouse_id'       => $order->warehouse_id,
                    'batch_id'           => $batchId,
                    'lot_id'             => $lotId,
                    'quantity'           => $quantityProduced,
                    'unit_cost'          => $unitCost,
                    'movement_type'      => 'production_produce',
                    'source_document_type' => 'production_order',
                    'source_document_id'   => $order->id,
                    'movement_date'        => now(),
                    'manufacture_date'     => $outputData['manufacture_date'] ?? today()->format('Y-m-d'),
                    'expiry_date'          => $outputData['expiry_date'] ?? null,
                ]);
            }

            // Record scrap
            if ($quantityScrapped > 0) {
                $order->update(['quantity_scrapped' => $quantityScrapped]);
            }

            // Calculate actual cost
            $actualCost = $order->lines->sum(fn ($l) => $l->quantity_issued * ($l->unit_cost ?? 0));

            $order->update([
                'status'             => 'completed',
                'quantity_produced'  => $quantityProduced,
                'quantity_scrapped'  => $quantityScrapped,
                'actual_cost'        => $actualCost,
                'output_batch_id'    => $batchId,
                'output_lot_id'      => $lotId,
                'actual_end_at'      => now(),
            ]);

            return $order->fresh();
        });
    }

    private function populateLinesFromBom(ProductionOrder $order, BillOfMaterial $bom, float $qty): void
    {
        $scaleFactor = $qty / $bom->output_quantity;

        foreach ($bom->components as $component) {
            $requiredQty = $component->quantity * $scaleFactor;
            // Add scrap allowance
            $requiredWithScrap = $requiredQty * (1 + ($component->scrap_percentage / 100));

            ProductionOrderLine::create([
                'production_order_id' => $order->id,
                'product_id'          => $component->product_id,
                'product_variant_id'  => $component->product_variant_id,
                'uom_id'              => $component->uom_id,
                'quantity_required'   => round($requiredWithScrap, 4),
                'quantity_issued'     => 0,
                'quantity_returned'   => 0,
                'unit_cost'           => \App\Models\Product::find($component->product_id)?->standard_cost ?? 0,
                'status'              => 'pending',
            ]);
        }
    }
}

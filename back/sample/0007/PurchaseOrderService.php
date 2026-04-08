<?php

namespace App\Services;

use App\Models\{
    PurchaseOrder, PurchaseOrderLine, GoodsReceipt, GoodsReceiptLine,
    Batch, Lot, DocumentSequence, ReorderRule, Supplier, Product
};
use App\Services\Inventory\InventoryEngine;
use Illuminate\Support\Facades\DB;

/**
 * PurchaseOrderService
 *
 * Manages the full procurement lifecycle:
 *   Draft → Pending Approval → Approved → Sent → Partially Received → Received → Closed
 *
 * On GRN posting, delegates stock movements to InventoryEngine which handles
 * costing layer creation, ledger writes, and AVCO recalculation.
 */
class PurchaseOrderService
{
    public function __construct(
        private InventoryEngine $inventory,
        private DocumentSequenceService $sequences,
    ) {}

    // ── Create PO ────────────────────────────────────────────────────────────
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create(array_merge($data, [
                'po_number' => $this->sequences->next($data['organization_id'], 'po'),
                'status'    => 'draft',
            ]));

            foreach ($data['lines'] as $i => $line) {
                PurchaseOrderLine::create(array_merge($line, [
                    'purchase_order_id' => $po->id,
                    'line_number'       => $i + 1,
                    'status'            => 'open',
                    'line_total'        => $line['quantity_ordered'] * $line['unit_cost'],
                ]));
            }

            $this->recalculateTotals($po);
            return $po->fresh(['lines']);
        });
    }

    // ── Approve PO ───────────────────────────────────────────────────────────
    public function approve(PurchaseOrder $po): PurchaseOrder
    {
        if (!in_array($po->status, ['draft', 'pending_approval'])) {
            throw new \RuntimeException("Cannot approve a PO in status: {$po->status}");
        }

        $po->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $po->auditEvent('approved', ['status' => $po->getOriginal('status')], ['status' => 'approved']);
        return $po;
    }

    // ── Create Goods Receipt (GRN) ────────────────────────────────────────────
    public function createGoodsReceipt(PurchaseOrder $po, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $data) {
            if (!in_array($po->status, ['approved', 'sent', 'partially_received'])) {
                throw new \RuntimeException("Cannot receive against PO in status: {$po->status}");
            }

            $grn = GoodsReceipt::create([
                'organization_id'      => $po->organization_id,
                'purchase_order_id'    => $po->id,
                'supplier_id'          => $po->supplier_id,
                'warehouse_id'         => $po->warehouse_id,
                'received_by'          => auth()->id(),
                'grn_number'           => $this->sequences->next($po->organization_id, 'grn'),
                'status'               => 'draft',
                'receipt_date'         => $data['receipt_date'] ?? today(),
                'supplier_delivery_note' => $data['supplier_delivery_note'] ?? null,
                'tracking_number'      => $data['tracking_number'] ?? null,
                'carrier'              => $data['carrier'] ?? null,
                'notes'                => $data['notes'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                $poLine = PurchaseOrderLine::findOrFail($line['purchase_order_line_id']);

                // Create or resolve batch/lot for tracking
                $batchId = $this->resolveBatch($po, $poLine, $line);
                $lotId   = $this->resolveLot($po, $grn, $poLine, $line, $batchId);

                GoodsReceiptLine::create([
                    'goods_receipt_id'      => $grn->id,
                    'purchase_order_line_id'=> $poLine->id,
                    'product_id'            => $poLine->product_id,
                    'product_variant_id'    => $poLine->product_variant_id,
                    'storage_location_id'   => $line['storage_location_id'] ?? null,
                    'batch_id'              => $batchId,
                    'lot_id'                => $lotId,
                    'uom_id'                => $poLine->uom_id,
                    'quantity_expected'     => $poLine->quantity_ordered - $poLine->quantity_received,
                    'quantity_received'     => $line['quantity_received'],
                    'quantity_accepted'     => $line['quantity_accepted'] ?? $line['quantity_received'],
                    'quantity_rejected'     => $line['quantity_rejected'] ?? 0,
                    'unit_cost'             => $poLine->unit_cost,
                    'line_total'            => $line['quantity_received'] * $poLine->unit_cost,
                    'rejection_reason'      => $line['rejection_reason'] ?? null,
                    'condition'             => $line['condition'] ?? 'new',
                    'notes'                 => $line['notes'] ?? null,
                ]);
            }

            return $grn->fresh(['lines']);
        });
    }

    // ── Post GRN — triggers stock movements ───────────────────────────────────
    public function postGoodsReceipt(GoodsReceipt $grn): GoodsReceipt
    {
        return DB::transaction(function () use ($grn) {
            if ($grn->status !== 'qc_approved' && $grn->status !== 'draft') {
                throw new \RuntimeException("GRN must be QC approved before posting.");
            }

            foreach ($grn->lines as $line) {
                if ($line->quantity_accepted <= 0) continue;

                // Stock IN via InventoryEngine — full costing/ledger pipeline
                $this->inventory->receiveStock([
                    'organization_id'     => $grn->organization_id,
                    'product_id'          => $line->product_id,
                    'product_variant_id'  => $line->product_variant_id,
                    'warehouse_id'        => $grn->warehouse_id,
                    'storage_location_id' => $line->storage_location_id,
                    'lot_id'              => $line->lot_id,
                    'batch_id'            => $line->batch_id,
                    'uom_id'              => $line->uom_id,
                    'quantity'            => $line->quantity_accepted,
                    'unit_cost'           => $line->unit_cost,
                    'movement_type'       => 'purchase_receipt',
                    'source_document_type'=> 'goods_receipt',
                    'source_document_id'  => $grn->id,
                    'source_document_number' => $grn->grn_number,
                    'source_line_id'      => $line->id,
                    'movement_date'       => $grn->receipt_date,
                    'expiry_date'         => $line->batch?->expiry_date ?? $line->lot?->expiry_date,
                    'manufacture_date'    => $line->batch?->manufacture_date ?? $line->lot?->manufacture_date,
                ]);

                // Update PO line received qty
                $line->purchaseOrderLine?->increment('quantity_received', $line->quantity_accepted);
            }

            // Update GRN status
            $grn->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'total_received_value' => $grn->lines->sum('line_total'),
            ]);

            // Update PO status
            $this->updatePoStatus($grn->purchaseOrder);

            return $grn->fresh();
        });
    }

    // ── Auto-create PO from reorder rule ─────────────────────────────────────
    public function createFromReorderRule(ReorderRule $rule, float $quantity): PurchaseOrder
    {
        $supplier = $rule->preferredSupplier ?? Supplier::where('organization_id', $rule->organization_id)
            ->whereHas('supplierProducts', fn ($q) => $q->where('product_id', $rule->product_id))
            ->first();

        if (!$supplier) {
            throw new \RuntimeException("No supplier found for auto-reorder of product #{$rule->product_id}");
        }

        $supplierProduct = $supplier->supplierProducts()
            ->where('product_id', $rule->product_id)
            ->first();

        $unitCost = $supplierProduct?->unit_cost ?? $rule->product->standard_cost ?? 0;

        return $this->create([
            'organization_id' => $rule->organization_id,
            'supplier_id'     => $supplier->id,
            'warehouse_id'    => $rule->warehouse_id ?? throw new \RuntimeException('Warehouse required for auto-PO'),
            'created_by'      => 1, // system user
            'order_date'      => today(),
            'expected_delivery_date' => today()->addDays($supplier->lead_time_days ?? 7),
            'notes'           => "Auto-generated by reorder rule #{$rule->id}",
            'lines' => [[
                'product_id'       => $rule->product_id,
                'product_variant_id' => $rule->product_variant_id,
                'quantity_ordered'  => max($quantity, $supplierProduct?->minimum_order_qty ?? 1),
                'unit_cost'         => $unitCost,
                'uom_id'            => $supplierProduct?->uom_id,
            ]],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveBatch(PurchaseOrder $po, PurchaseOrderLine $line, array $receiptLine): ?int
    {
        $product = Product::find($line->product_id);
        if (!$product?->track_batches) return null;

        if (!empty($receiptLine['batch_id'])) {
            return $receiptLine['batch_id'];
        }

        if (!empty($receiptLine['batch_number'])) {
            $batch = Batch::firstOrCreate(
                ['organization_id' => $po->organization_id, 'batch_number' => $receiptLine['batch_number']],
                [
                    'product_id'          => $line->product_id,
                    'product_variant_id'  => $line->product_variant_id,
                    'status'              => 'active',
                    'manufacture_date'    => $receiptLine['manufacture_date'] ?? null,
                    'expiry_date'         => $receiptLine['expiry_date'] ?? null,
                    'best_before_date'    => $receiptLine['best_before_date'] ?? null,
                    'received_date'       => today(),
                    'supplier_name'       => $po->supplier->name,
                    'unit_cost'           => $line->unit_cost,
                    'currency'            => $po->currency,
                    'qc_status'           => 'pending',
                ]
            );
            return $batch->id;
        }

        return null;
    }

    private function resolveLot(PurchaseOrder $po, GoodsReceipt $grn, PurchaseOrderLine $line, array $receiptLine, ?int $batchId): ?int
    {
        $product = Product::find($line->product_id);
        if (!$product?->track_lots) return null;

        if (!empty($receiptLine['lot_id'])) {
            return $receiptLine['lot_id'];
        }

        // Auto-generate lot number if not provided
        $lotNumber = $receiptLine['lot_number'] ?? ($grn->grn_number . '-L' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT));

        $lot = Lot::create([
            'organization_id'     => $po->organization_id,
            'batch_id'            => $batchId,
            'product_id'          => $line->product_id,
            'product_variant_id'  => $line->product_variant_id,
            'warehouse_id'        => $po->warehouse_id,
            'storage_location_id' => $receiptLine['storage_location_id'] ?? null,
            'lot_number'          => $lotNumber,
            'status'              => 'available',
            'initial_quantity'    => $receiptLine['quantity_accepted'] ?? $receiptLine['quantity_received'],
            'available_quantity'  => $receiptLine['quantity_accepted'] ?? $receiptLine['quantity_received'],
            'reserved_quantity'   => 0,
            'unit_cost'           => $line->unit_cost,
            'valuation_method'    => null, // resolved by engine
            'manufacture_date'    => $receiptLine['manufacture_date'] ?? null,
            'expiry_date'         => $receiptLine['expiry_date'] ?? null,
            'best_before_date'    => $receiptLine['best_before_date'] ?? null,
            'received_at'         => now(),
        ]);

        return $lot->id;
    }

    private function recalculateTotals(PurchaseOrder $po): void
    {
        $po->load('lines');
        $subtotal = $po->lines->sum('line_total');

        $po->update([
            'subtotal'     => $subtotal,
            'total_amount' => $subtotal + $po->tax_amount + $po->shipping_cost + $po->other_charges - $po->discount_amount,
        ]);
    }

    private function updatePoStatus(PurchaseOrder $po): void
    {
        $po->load('lines');
        $allReceived     = $po->lines->every(fn ($l) => $l->quantity_received >= $l->quantity_ordered);
        $someReceived    = $po->lines->some(fn ($l) => $l->quantity_received > 0);
        $allCancelled    = $po->lines->every(fn ($l) => $l->status === 'cancelled');

        $newStatus = match(true) {
            $allCancelled    => 'cancelled',
            $allReceived     => 'received',
            $someReceived    => 'partially_received',
            default          => $po->status,
        };

        if ($newStatus !== $po->status) {
            $po->update(['status' => $newStatus]);
        }
    }
}

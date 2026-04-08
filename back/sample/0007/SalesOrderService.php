<?php

namespace App\Services;

use App\Models\{
    SalesOrder, SalesOrderLine, StockAllocation, PickList,
    Shipment, ShipmentItem, ReturnAuthorization, ReturnAuthorizationLine
};
use App\Services\Inventory\{InventoryEngine, AllocationService};
use Illuminate\Support\Facades\DB;

/**
 * SalesOrderService
 *
 * Manages the complete outbound order lifecycle:
 *   Draft → Confirmed → Picking → Packed → Shipped → Delivered
 *
 * Supports:
 *  - Multi-channel orders (POS, e-commerce, wholesale, API, marketplace)
 *  - All 8 allocation algorithms (delegated to AllocationService)
 *  - Partial fulfillment with backorder management
 *  - Bundle/combo/kit line expansion
 *  - Wave, batch, cluster pick list generation
 *  - Full return / RMA workflow
 */
class SalesOrderService
{
    public function __construct(
        private InventoryEngine    $inventory,
        private AllocationService  $allocation,
        private DocumentSequenceService $sequences,
    ) {}

    // ── Create Order ──────────────────────────────────────────────────────────
    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            $order = SalesOrder::create(array_merge($data, [
                'order_number'       => $this->sequences->next($data['organization_id'], 'so'),
                'status'             => 'draft',
                'fulfillment_status' => 'unfulfilled',
                'payment_status'     => 'unpaid',
            ]));

            foreach ($data['lines'] as $i => $line) {
                $lineModel = SalesOrderLine::create(array_merge($line, [
                    'sales_order_id' => $order->id,
                    'line_number'    => $i + 1,
                    'status'         => 'open',
                    'line_total'     => $this->calcLineTotal($line),
                ]));

                // Expand bundle/combo components into child lines
                if ($this->isComposite($line['product_id'])) {
                    $this->expandCompositeLines($order, $lineModel);
                }
            }

            $this->recalculateTotals($order);
            return $order->fresh(['lines.product', 'customer']);
        });
    }

    // ── Confirm Order ─────────────────────────────────────────────────────────
    public function confirm(SalesOrder $order): SalesOrder
    {
        return DB::transaction(function () use ($order) {
            if ($order->status !== 'draft') {
                throw new \RuntimeException("Only draft orders can be confirmed.");
            }

            $order->update(['status' => 'confirmed']);

            // Run allocation using the order's configured algorithm
            $algorithm = $order->allocation_algorithm
                ?? \App\Models\InventorySettings::where('organization_id', $order->organization_id)
                    ->value('default_allocation_algorithm')
                ?? 'soft_reservation';

            $this->allocation->allocate($order, $algorithm);

            return $order->fresh();
        });
    }

    // ── Generate Pick List ────────────────────────────────────────────────────
    public function generatePickList(SalesOrder $order, string $type = 'single'): PickList
    {
        return DB::transaction(function () use ($order, $type) {
            $orders = collect([$order]);

            return match($type) {
                'wave'    => $this->allocation->wavePicking($orders),
                'batch'   => $this->allocation->batchPicking($orders),
                'cluster' => $this->allocation->clusterPicking($orders),
                default   => $this->allocation->wavePicking($orders),
            };
        });
    }

    // ── Record Pick ───────────────────────────────────────────────────────────
    public function recordPick(int $pickListLineId, float $quantityPicked, ?string $shortReason = null): void
    {
        DB::transaction(function () use ($pickListLineId, $quantityPicked, $shortReason) {
            $pickLine = \App\Models\PickListLine::findOrFail($pickListLineId);

            $pickLine->update([
                'quantity_picked' => $quantityPicked,
                'status'          => $quantityPicked >= $pickLine->quantity_to_pick ? 'picked' : 'short_picked',
                'short_reason'    => $quantityPicked < $pickLine->quantity_to_pick ? $shortReason : null,
                'picked_at'       => now(),
            ]);

            // Update order line
            $pickLine->salesOrderLine->increment('quantity_picked', $quantityPicked);
        });
    }

    // ── Ship Order ────────────────────────────────────────────────────────────
    public function ship(SalesOrder $order, array $shipmentData): Shipment
    {
        return DB::transaction(function () use ($order, $shipmentData) {
            $shipment = Shipment::create([
                'organization_id' => $order->organization_id,
                'warehouse_id'    => $order->warehouse_id,
                'created_by'      => auth()->id(),
                'shipment_number' => $this->sequences->next($order->organization_id, 'shipment'),
                'status'          => 'shipped',
                'carrier'         => $shipmentData['carrier'] ?? null,
                'service_level'   => $shipmentData['service_level'] ?? null,
                'tracking_number' => $shipmentData['tracking_number'] ?? null,
                'shipping_cost'   => $shipmentData['shipping_cost'] ?? null,
                'shipping_address'=> $order->shipping_address,
                'shipped_at'      => now(),
                'estimated_delivery_date' => $shipmentData['estimated_delivery_date'] ?? null,
            ]);

            foreach ($shipmentData['lines'] as $line) {
                $orderLine = SalesOrderLine::findOrFail($line['sales_order_line_id']);
                $qty       = $line['quantity'];

                // Issue stock via InventoryEngine
                $this->inventory->issueStock([
                    'organization_id'    => $order->organization_id,
                    'product_id'         => $orderLine->product_id,
                    'product_variant_id' => $orderLine->product_variant_id,
                    'warehouse_id'       => $order->warehouse_id,
                    'storage_location_id'=> $line['storage_location_id'] ?? null,
                    'lot_id'             => $line['lot_id'] ?? null,
                    'batch_id'           => $line['batch_id'] ?? null,
                    'serial_number_id'   => $line['serial_number_id'] ?? null,
                    'quantity'           => $qty,
                    'movement_type'      => 'sales_issue',
                    'rotation_strategy'  => $shipmentData['rotation_strategy'] ?? null,
                    'source_document_type' => 'sales_order',
                    'source_document_id'   => $order->id,
                    'source_document_number' => $order->order_number,
                    'source_line_id'       => $orderLine->id,
                    'movement_date'        => now(),
                ]);

                ShipmentItem::create([
                    'shipment_id'         => $shipment->id,
                    'sales_order_id'      => $order->id,
                    'sales_order_line_id' => $orderLine->id,
                    'product_id'          => $orderLine->product_id,
                    'product_variant_id'  => $orderLine->product_variant_id,
                    'lot_id'              => $line['lot_id'] ?? null,
                    'batch_id'            => $line['batch_id'] ?? null,
                    'serial_number_id'    => $line['serial_number_id'] ?? null,
                    'quantity'            => $qty,
                ]);

                $orderLine->increment('quantity_shipped', $qty);

                // Fulfill the allocation
                StockAllocation::where('sales_order_line_id', $orderLine->id)
                    ->where('status', 'active')
                    ->update(['status' => 'fulfilled', 'fulfilled_at' => now(), 'quantity_fulfilled' => $qty]);
            }

            // Update order fulfillment status
            $this->updateFulfillmentStatus($order);

            return $shipment;
        });
    }

    // ── Create RMA ────────────────────────────────────────────────────────────
    public function createReturn(SalesOrder $order, array $data): ReturnAuthorization
    {
        return DB::transaction(function () use ($order, $data) {
            $rma = ReturnAuthorization::create([
                'organization_id'  => $order->organization_id,
                'customer_id'      => $order->customer_id,
                'sales_order_id'   => $order->id,
                'warehouse_id'     => $data['warehouse_id'] ?? $order->warehouse_id,
                'created_by'       => auth()->id(),
                'rma_number'       => $this->sequences->next($order->organization_id, 'rma'),
                'status'           => 'pending',
                'reason_category'  => $data['reason_category'],
                'customer_reason'  => $data['customer_reason'] ?? null,
                'resolution_type'  => $data['resolution_type'],
                'requested_date'   => today(),
            ]);

            foreach ($data['lines'] as $line) {
                ReturnAuthorizationLine::create([
                    'return_authorization_id' => $rma->id,
                    'sales_order_line_id'     => $line['sales_order_line_id'] ?? null,
                    'product_id'              => $line['product_id'],
                    'product_variant_id'      => $line['product_variant_id'] ?? null,
                    'lot_id'                  => $line['lot_id'] ?? null,
                    'batch_id'                => $line['batch_id'] ?? null,
                    'serial_number_id'        => $line['serial_number_id'] ?? null,
                    'quantity_requested'      => $line['quantity'],
                    'unit_price'              => $line['unit_price'],
                ]);
            }

            return $rma->fresh(['lines']);
        });
    }

    // ── Receive and Disposition Return ────────────────────────────────────────
    public function receiveReturn(ReturnAuthorization $rma, array $data): ReturnAuthorization
    {
        return DB::transaction(function () use ($rma, $data) {
            foreach ($data['lines'] as $lineData) {
                $rmaLine = \App\Models\ReturnAuthorizationLine::findOrFail($lineData['return_authorization_line_id']);
                $qtyReceived  = $lineData['quantity_received'];
                $qtyRestock   = $lineData['quantity_restock'] ?? 0;
                $qtyScrap     = $lineData['quantity_scrap'] ?? ($qtyReceived - $qtyRestock);

                $rmaLine->update([
                    'quantity_received'  => $qtyReceived,
                    'quantity_restocked' => $qtyRestock,
                    'quantity_scrapped'  => $qtyScrap,
                    'condition'          => $lineData['condition'] ?? 'good',
                    'disposition'        => $lineData['disposition'] ?? 'restock',
                    'inspection_notes'   => $lineData['inspection_notes'] ?? null,
                    'storage_location_id'=> $lineData['storage_location_id'] ?? null,
                ]);

                // Restock: put back in stock
                if ($qtyRestock > 0) {
                    $this->inventory->receiveStock([
                        'organization_id'     => $rma->organization_id,
                        'product_id'          => $rmaLine->product_id,
                        'product_variant_id'  => $rmaLine->product_variant_id,
                        'warehouse_id'        => $rma->warehouse_id,
                        'storage_location_id' => $lineData['storage_location_id'] ?? null,
                        'lot_id'              => $rmaLine->lot_id,
                        'batch_id'            => $rmaLine->batch_id,
                        'serial_number_id'    => $rmaLine->serial_number_id,
                        'quantity'            => $qtyRestock,
                        'unit_cost'           => $rmaLine->unit_price,
                        'movement_type'       => 'sales_return',
                        'source_document_type'=> 'return_authorization',
                        'source_document_id'  => $rma->id,
                        'movement_date'       => now(),
                    ]);
                }

                // Scrap: write off
                if ($qtyScrap > 0) {
                    $this->inventory->issueStock([
                        'organization_id'    => $rma->organization_id,
                        'product_id'         => $rmaLine->product_id,
                        'warehouse_id'       => $rma->warehouse_id,
                        'quantity'           => $qtyScrap,
                        'movement_type'      => 'scrap',
                        'source_document_type' => 'return_authorization',
                        'source_document_id'   => $rma->id,
                        'movement_date'        => now(),
                        'notes'              => 'Returned item deemed non-resalable',
                    ]);
                }
            }

            $rma->update(['status' => 'received', 'received_date' => today()]);
            return $rma->fresh();
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function expandCompositeLines(SalesOrder $order, SalesOrderLine $parentLine): void
    {
        $components = \App\Models\ProductComponent::where('parent_product_id', $parentLine->product_id)
            ->where('deduct_from_stock_separately', true)
            ->get();

        foreach ($components as $component) {
            SalesOrderLine::create([
                'sales_order_id'     => $order->id,
                'product_id'         => $component->component_product_id,
                'product_variant_id' => $component->component_variant_id,
                'line_number'        => null, // child line
                'quantity_ordered'   => $parentLine->quantity_ordered * $component->quantity,
                'unit_price'         => 0, // price is on parent
                'line_total'         => 0,
                'status'             => 'open',
                'component_details'  => ['parent_line_id' => $parentLine->id, 'component_id' => $component->id],
            ]);
        }
    }

    private function isComposite(int $productId): bool
    {
        return \App\Models\Product::where('id', $productId)
            ->where(fn ($q) => $q->where('is_composite', true)->orWhere('is_kit', true))
            ->exists();
    }

    private function calcLineTotal(array $line): float
    {
        $subtotal  = $line['quantity_ordered'] * $line['unit_price'];
        $discount  = $line['discount_amount'] ?? ($subtotal * (($line['discount_percentage'] ?? 0) / 100));
        $taxBase   = $subtotal - $discount;
        $tax       = $taxBase * (($line['tax_rate'] ?? 0) / 100);
        return round($taxBase + $tax, 4);
    }

    private function recalculateTotals(SalesOrder $order): void
    {
        $order->load('lines');
        $subtotal = $order->lines->sum(fn ($l) => $l->quantity_ordered * $l->unit_price);
        $discount = $order->lines->sum('discount_amount');
        $tax      = $order->lines->sum('tax_amount');

        $order->update([
            'subtotal'       => $subtotal,
            'discount_amount'=> $discount,
            'tax_amount'     => $tax,
            'total_amount'   => $subtotal - $discount + $tax + ($order->shipping_amount ?? 0),
        ]);
    }

    private function updateFulfillmentStatus(SalesOrder $order): void
    {
        $order->load('lines');
        $allShipped  = $order->lines->every(fn ($l) => $l->quantity_shipped >= $l->quantity_ordered);
        $someShipped = $order->lines->some(fn ($l) => $l->quantity_shipped > 0);

        $order->update([
            'fulfillment_status' => match(true) {
                $allShipped  => 'fulfilled',
                $someShipped => 'partially_fulfilled',
                default      => 'unfulfilled',
            },
            'status' => $allShipped ? 'shipped' : ($someShipped ? 'partially_picked' : $order->status),
        ]);
    }
}

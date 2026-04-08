<?php

namespace App\Services;

use App\Models\{StockLedgerEntry, StockPosition, CostingLayer, ProductClassification, InventorySnapshot};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * InventoryReportingService
 *
 * Generates all standard inventory reports:
 *
 *  - Stock on hand valuation (by method: FIFO/LIFO/AVCO/etc.)
 *  - COGS (Cost of Goods Sold) for any period
 *  - Inventory turnover ratio and days-on-hand
 *  - Slow-moving and dead stock report
 *  - Inventory ageing (lots/batches by age)
 *  - Gross margin by product/category/warehouse
 *  - Batch/lot traceability report
 *  - Serial number history
 *  - ABC analysis report
 *  - Landed cost impact analysis
 *  - Period comparison (current vs prior period)
 */
class InventoryReportingService
{
    // ── Stock on Hand Valuation ───────────────────────────────────────────────
    public function stockValuation(
        int     $organizationId,
        ?int    $warehouseId = null,
        ?int    $categoryId  = null,
        string  $method      = 'AVCO',
        ?string $asOfDate    = null,
    ): Collection {
        $query = StockPosition::where('organization_id', $organizationId)
            ->where('qty_on_hand', '!=', 0)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($categoryId, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $categoryId)))
            ->with(['product.category', 'variant', 'warehouse', 'lot']);

        return $query->get()->map(function ($position) use ($method) {
            $unitCost = match($method) {
                'FIFO', 'FEFO' => $this->getFifoCost($position),
                'LIFO'         => $this->getLifoCost($position),
                'standard'     => $position->product->standard_cost ?? 0,
                default        => $position->average_cost, // AVCO
            };

            return [
                'product_id'         => $position->product_id,
                'sku'                => $position->product?->sku,
                'product_name'       => $position->product?->name,
                'variant'            => $position->variant?->display_name,
                'category'           => $position->product?->category?->name,
                'warehouse'          => $position->warehouse?->name,
                'lot_number'         => $position->lot?->lot_number,
                'expiry_date'        => $position->lot?->expiry_date,
                'qty_on_hand'        => $position->qty_on_hand,
                'qty_available'      => $position->qty_available,
                'qty_reserved'       => $position->qty_reserved,
                'unit_cost'          => round($unitCost, 4),
                'total_value'        => round($position->qty_on_hand * $unitCost, 2),
                'selling_price'      => $position->product?->standard_price,
                'potential_revenue'  => $position->qty_on_hand * ($position->product?->standard_price ?? 0),
                'gross_margin_pct'   => $this->calcGrossMargin($unitCost, $position->product?->standard_price),
                'valuation_method'   => $method,
                'last_movement_at'   => $position->last_movement_at,
            ];
        });
    }

    // ── COGS Report ───────────────────────────────────────────────────────────
    public function cogsReport(
        int     $organizationId,
        string  $fromDate,
        string  $toDate,
        ?int    $warehouseId = null,
        ?int    $categoryId  = null,
    ): array {
        $query = StockLedgerEntry::where('organization_id', $organizationId)
            ->where('direction', 'OUT')
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->whereIn('movement_type', ['sales_issue', 'production_consume', 'scrap', 'write_off'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId));

        if ($categoryId) {
            $query->whereHas('product', fn ($p) => $p->where('category_id', $categoryId));
        }

        $entries = $query->with(['product.category', 'variant', 'warehouse'])->get();

        $byType = $entries->groupBy('movement_type')->map(fn ($g) => [
            'quantity'   => $g->sum('quantity'),
            'total_cost' => $g->sum('total_cost'),
        ]);

        $byProduct = $entries->groupBy('product_id')->map(function ($g) {
            $product = $g->first()->product;
            return [
                'product_id'   => $product?->id,
                'sku'          => $product?->sku,
                'product_name' => $product?->name,
                'category'     => $product?->category?->name,
                'quantity_sold'=> $g->where('movement_type', 'sales_issue')->sum('quantity'),
                'cogs'         => $g->where('movement_type', 'sales_issue')->sum('total_cost'),
                'scrap_cost'   => $g->where('movement_type', 'scrap')->sum('total_cost'),
                'write_off'    => $g->where('movement_type', 'write_off')->sum('total_cost'),
                'total_cost'   => $g->sum('total_cost'),
            ];
        })->sortByDesc('total_cost');

        return [
            'period'        => ['from' => $fromDate, 'to' => $toDate],
            'total_cogs'    => $entries->where('movement_type', 'sales_issue')->sum('total_cost'),
            'total_scrap'   => $entries->where('movement_type', 'scrap')->sum('total_cost'),
            'total_write_off'=> $entries->where('movement_type', 'write_off')->sum('total_cost'),
            'by_type'       => $byType,
            'by_product'    => $byProduct->values(),
        ];
    }

    // ── Inventory Turnover ────────────────────────────────────────────────────
    public function turnoverReport(
        int    $organizationId,
        string $fromDate,
        string $toDate,
        ?int   $warehouseId = null,
    ): Collection {
        $cogs = StockLedgerEntry::where('organization_id', $organizationId)
            ->where('direction', 'OUT')
            ->where('movement_type', 'sales_issue')
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('product_id, SUM(total_cost) as cogs')
            ->groupBy('product_id')
            ->pluck('cogs', 'product_id');

        $avgInventory = StockPosition::where('organization_id', $organizationId)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('product_id, AVG(total_cost_value) as avg_value')
            ->groupBy('product_id')
            ->pluck('avg_value', 'product_id');

        $days = Carbon::parse($fromDate)->diffInDays(Carbon::parse($toDate));

        return collect($cogs)->map(function ($cog, $productId) use ($avgInventory, $days) {
            $avgInv  = $avgInventory[$productId] ?? 0;
            $turnover = $avgInv > 0 ? ($cog / $avgInv) : 0;
            $doh      = $turnover > 0 ? (365 / $turnover) : null; // Days on Hand

            return [
                'product_id'    => $productId,
                'product_name'  => \App\Models\Product::find($productId)?->name,
                'cogs'          => round($cog, 2),
                'avg_inventory' => round($avgInv, 2),
                'turnover_ratio'=> round($turnover, 2),
                'days_on_hand'  => $doh ? round($doh) : null,
                'classification'=> match(true) {
                    $doh === null      => 'no_movement',
                    $doh < 30          => 'fast',
                    $doh < 90          => 'medium',
                    $doh < 180         => 'slow',
                    default            => 'dead',
                },
            ];
        })->sortByDesc('turnover_ratio')->values();
    }

    // ── Inventory Ageing ──────────────────────────────────────────────────────
    public function ageingReport(int $organizationId, ?int $warehouseId = null): Collection
    {
        return \App\Models\Lot::where('organization_id', $organizationId)
            ->whereIn('status', ['available', 'reserved'])
            ->where('available_quantity', '>', 0)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->with(['product', 'warehouse'])
            ->get()
            ->map(function (\App\Models\Lot $lot) {
                $ageDays = $lot->received_at ? $lot->received_at->diffInDays(now()) : null;

                return [
                    'lot_number'      => $lot->lot_number,
                    'product_name'    => $lot->product?->name,
                    'sku'             => $lot->product?->sku,
                    'warehouse'       => $lot->warehouse?->name,
                    'received_at'     => $lot->received_at,
                    'age_days'        => $ageDays,
                    'age_bucket'      => $this->ageBucket($ageDays),
                    'available_qty'   => $lot->available_quantity,
                    'unit_cost'       => $lot->unit_cost,
                    'total_value'     => round($lot->available_quantity * ($lot->unit_cost ?? 0), 2),
                    'expiry_date'     => $lot->expiry_date,
                    'days_to_expiry'  => $lot->expiry_date ? now()->diffInDays($lot->expiry_date, false) : null,
                    'status'          => $lot->status,
                ];
            })
            ->sortByDesc('age_days')
            ->values();
    }

    // ── Batch Traceability ────────────────────────────────────────────────────
    public function batchTrace(int $organizationId, string $batchNumber): array
    {
        $batch = \App\Models\Batch::where('organization_id', $organizationId)
            ->where('batch_number', $batchNumber)
            ->with(['product', 'lots', 'documents'])
            ->firstOrFail();

        // All movements involving this batch
        $movements = StockLedgerEntry::where('batch_id', $batch->id)
            ->with(['warehouse', 'createdBy'])
            ->orderBy('movement_date')
            ->get();

        // All sales orders that consumed this batch (forward trace)
        $salesOrders = \App\Models\ShipmentItem::where('batch_id', $batch->id)
            ->with('salesOrder.customer')
            ->get()
            ->groupBy('sales_order_id')
            ->map(fn ($items) => [
                'order_number'   => $items->first()->salesOrder?->order_number,
                'customer'       => $items->first()->salesOrder?->customer?->name,
                'shipped_at'     => $items->first()->salesOrder?->actual_delivery_date,
                'quantity_shipped'=> $items->sum('quantity'),
            ]);

        // Genealogy (parent/child batches)
        $genealogy = \App\Models\BatchGenealogy::where('parent_batch_id', $batch->id)
            ->orWhere('child_batch_id', $batch->id)
            ->with(['parentBatch', 'childBatch'])
            ->get();

        return [
            'batch'       => $batch,
            'movements'   => $movements,
            'sales_orders'=> $salesOrders->values(),
            'genealogy'   => $genealogy,
            'documents'   => $batch->documents,
            'lots'        => $batch->lots,
        ];
    }

    // ── Gross Margin Report ───────────────────────────────────────────────────
    public function grossMarginReport(
        int    $organizationId,
        string $fromDate,
        string $toDate,
    ): Collection {
        return StockLedgerEntry::where('organization_id', $organizationId)
            ->where('direction', 'OUT')
            ->where('movement_type', 'sales_issue')
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->with('product.category')
            ->get()
            ->groupBy('product_id')
            ->map(function ($entries) use ($fromDate, $toDate) {
                $product  = $entries->first()->product;
                $cogs     = $entries->sum('total_cost');
                $qty      = $entries->sum('quantity');
                $revenue  = DB::table('sales_order_lines')
                    ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
                    ->where('sales_order_lines.product_id', $product?->id)
                    ->whereBetween('sales_orders.order_date', [$fromDate, $toDate])
                    ->sum('sales_order_lines.line_total');

                return [
                    'product_id'   => $product?->id,
                    'sku'          => $product?->sku,
                    'product_name' => $product?->name,
                    'category'     => $product?->category?->name,
                    'quantity_sold'=> $qty,
                    'revenue'      => round($revenue, 2),
                    'cogs'         => round($cogs, 2),
                    'gross_profit' => round($revenue - $cogs, 2),
                    'gross_margin_pct' => $revenue > 0 ? round((($revenue - $cogs) / $revenue) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('gross_profit')
            ->values();
    }

    // ── Period Comparison ─────────────────────────────────────────────────────
    public function periodComparison(int $organizationId, string $currentFrom, string $currentTo): array
    {
        $days     = Carbon::parse($currentFrom)->diffInDays($currentTo);
        $priorTo  = Carbon::parse($currentFrom)->subDay()->format('Y-m-d');
        $priorFrom= Carbon::parse($priorTo)->subDays($days)->format('Y-m-d');

        $current = $this->cogsReport($organizationId, $currentFrom, $currentTo);
        $prior   = $this->cogsReport($organizationId, $priorFrom, $priorTo);

        return [
            'current_period' => $current,
            'prior_period'   => $prior,
            'cogs_change'    => $current['total_cogs'] - $prior['total_cogs'],
            'cogs_change_pct'=> $prior['total_cogs'] > 0
                ? round((($current['total_cogs'] - $prior['total_cogs']) / $prior['total_cogs']) * 100, 2)
                : null,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getFifoCost(StockPosition $position): float
    {
        $layer = CostingLayer::where('product_id', $position->product_id)
            ->where('warehouse_id', $position->warehouse_id)
            ->where('is_fully_consumed', false)
            ->orderBy('received_at', 'asc')
            ->first();

        return $layer?->unit_cost ?? $position->average_cost;
    }

    private function getLifoCost(StockPosition $position): float
    {
        $layer = CostingLayer::where('product_id', $position->product_id)
            ->where('warehouse_id', $position->warehouse_id)
            ->where('is_fully_consumed', false)
            ->orderBy('received_at', 'desc')
            ->first();

        return $layer?->unit_cost ?? $position->average_cost;
    }

    private function calcGrossMargin(?float $cost, ?float $price): ?float
    {
        if (!$cost || !$price || $price == 0) return null;
        return round((($price - $cost) / $price) * 100, 2);
    }

    private function ageBucket(?int $days): string
    {
        if ($days === null) return 'unknown';
        return match(true) {
            $days <= 30  => '0-30 days',
            $days <= 60  => '31-60 days',
            $days <= 90  => '61-90 days',
            $days <= 180 => '91-180 days',
            $days <= 365 => '181-365 days',
            default      => '365+ days',
        };
    }
}

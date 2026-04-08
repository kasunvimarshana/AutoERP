<?php

namespace App\Services\Inventory;

use App\Models\{Product, StockPosition, InventorySettings, StockAlert, ReorderRule, Lot, Batch};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * AlertService
 *
 * Evaluates stock positions against configured thresholds and creates
 * actionable alerts. Works with the perpetual inventory method for
 * real-time alerting, or called at period-end for periodic.
 *
 * Alert Types:
 *   - low_stock         : qty_available <= reorder_point
 *   - out_of_stock      : qty_available <= 0
 *   - overstock         : qty_on_hand >= max_stock_level
 *   - expiring_soon     : lot/batch expiry within warning days
 *   - expired           : lot/batch past expiry
 *   - negative_stock    : qty_on_hand < 0 (when allowed)
 *   - reorder_point     : automated PO trigger
 */
class AlertService
{
    public function evaluate(
        Product           $product,
        StockPosition     $position,
        InventorySettings $settings,
    ): void {
        $this->checkLowStock($product, $position, $settings);
        $this->checkOverstock($product, $position);
        $this->checkNegativeStock($position, $settings);
    }

    /**
     * Full expiry scan — run daily via scheduled command.
     */
    public function scanExpiry(int $organizationId): void
    {
        $settings = InventorySettings::where('organization_id', $organizationId)->firstOrFail();

        if (!$settings->expiry_tracking_enabled) {
            return;
        }

        $warningDays = $settings->expiry_warning_days;
        $cutoffDate  = Carbon::now()->addDays($warningDays);

        // ── Expiring Soon (lots) ──────────────────────────────────────────────
        Lot::where('organization_id', $organizationId)
            ->whereIn('status', ['available', 'reserved'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $cutoffDate)
            ->where('expiry_date', '>', Carbon::now())
            ->where('available_quantity', '>', 0)
            ->each(function (Lot $lot) {
                $this->upsertAlert([
                    'organization_id' => $lot->organization_id,
                    'product_id'      => $lot->product_id,
                    'product_variant_id' => $lot->product_variant_id,
                    'warehouse_id'    => $lot->warehouse_id,
                    'alert_type'      => 'expiring_soon',
                    'current_quantity'=> $lot->available_quantity,
                    'expiry_date'     => $lot->expiry_date,
                ]);
            });

        // ── Expired (lots) ────────────────────────────────────────────────────
        Lot::where('organization_id', $organizationId)
            ->whereIn('status', ['available', 'reserved'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now())
            ->where('available_quantity', '>', 0)
            ->each(function (Lot $lot) {
                $this->upsertAlert([
                    'organization_id' => $lot->organization_id,
                    'product_id'      => $lot->product_id,
                    'warehouse_id'    => $lot->warehouse_id,
                    'alert_type'      => 'expired',
                    'current_quantity'=> $lot->available_quantity,
                    'expiry_date'     => $lot->expiry_date,
                ]);

                // Auto-update lot status
                $lot->update(['status' => 'expired']);
            });
    }

    /**
     * Trigger auto-reorder rules and optionally generate POs.
     */
    public function processReorderRules(int $organizationId): array
    {
        $triggered = [];

        ReorderRule::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->with(['product', 'preferredSupplier'])
            ->each(function (ReorderRule $rule) use (&$triggered) {
                $qty = $this->getCurrentAvailable(
                    $rule->product_id,
                    $rule->product_variant_id,
                    $rule->warehouse_id,
                );

                $shouldReorder = match($rule->method) {
                    'min_max'          => $qty <= $rule->min_qty,
                    'fixed_quantity'   => $qty <= ($rule->safety_stock ?? 0),
                    'days_of_supply'   => $this->daysOfSupply($rule->product_id, $rule->warehouse_id) <= ($rule->days_of_supply ?? 7),
                    'economic_order_qty' => $qty <= ($rule->safety_stock ?? 0),
                    default            => false,
                };

                if (!$shouldReorder) return;

                $orderQty = match($rule->method) {
                    'min_max'          => ($rule->max_qty - $qty),
                    'fixed_quantity'   => $rule->reorder_qty,
                    'economic_order_qty' => $rule->reorder_qty ?? $this->calcEoq($rule->product_id),
                    'days_of_supply'   => $this->calcDaysOfSupplyQty($rule),
                    default            => $rule->reorder_qty ?? 1,
                };

                $rule->update(['last_triggered_at' => now()]);

                $this->upsertAlert([
                    'organization_id' => $rule->organization_id,
                    'product_id'      => $rule->product_id,
                    'product_variant_id' => $rule->product_variant_id,
                    'warehouse_id'    => $rule->warehouse_id,
                    'alert_type'      => 'reorder_point',
                    'current_quantity'=> $qty,
                    'threshold_quantity' => $rule->min_qty,
                ]);

                $triggered[] = [
                    'rule'       => $rule,
                    'order_qty'  => max(0, $orderQty),
                    'current_qty'=> $qty,
                ];

                // Auto-generate PO if configured
                if ($rule->auto_generate_po && $rule->preferred_supplier_id) {
                    app(PurchaseOrderService::class)->createFromReorderRule($rule, $orderQty);
                }
            });

        return $triggered;
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private function checkLowStock(Product $product, StockPosition $position, InventorySettings $settings): void
    {
        $reorderPoint = $product->reorder_point;
        if ($reorderPoint === null) return;

        $available = StockPosition::where('product_id', $product->id)
            ->where('warehouse_id', $position->warehouse_id)
            ->sum('qty_available');

        if ($available <= 0) {
            $this->upsertAlert([
                'organization_id'    => $product->organization_id,
                'product_id'         => $product->id,
                'product_variant_id' => $position->product_variant_id,
                'warehouse_id'       => $position->warehouse_id,
                'alert_type'         => 'out_of_stock',
                'current_quantity'   => $available,
                'threshold_quantity' => 0,
            ]);
        } elseif ($available <= $reorderPoint) {
            $this->upsertAlert([
                'organization_id'    => $product->organization_id,
                'product_id'         => $product->id,
                'product_variant_id' => $position->product_variant_id,
                'warehouse_id'       => $position->warehouse_id,
                'alert_type'         => 'low_stock',
                'current_quantity'   => $available,
                'threshold_quantity' => $reorderPoint,
            ]);
        } else {
            // Resolve any existing low-stock/out-of-stock alert
            StockAlert::where('product_id', $product->id)
                ->where('warehouse_id', $position->warehouse_id)
                ->whereIn('alert_type', ['low_stock', 'out_of_stock'])
                ->where('status', 'active')
                ->update(['status' => 'resolved', 'resolved_at' => now()]);
        }
    }

    private function checkOverstock(Product $product, StockPosition $position): void
    {
        if (!$product->max_stock_level) return;

        $onHand = StockPosition::where('product_id', $product->id)
            ->where('warehouse_id', $position->warehouse_id)
            ->sum('qty_on_hand');

        if ($onHand >= $product->max_stock_level) {
            $this->upsertAlert([
                'organization_id'    => $product->organization_id,
                'product_id'         => $product->id,
                'warehouse_id'       => $position->warehouse_id,
                'alert_type'         => 'overstock',
                'current_quantity'   => $onHand,
                'threshold_quantity' => $product->max_stock_level,
            ]);
        }
    }

    private function checkNegativeStock(StockPosition $position, InventorySettings $settings): void
    {
        if ($position->qty_on_hand < 0 && $settings->warn_on_negative_stock) {
            $this->upsertAlert([
                'organization_id' => $position->organization_id,
                'product_id'      => $position->product_id,
                'warehouse_id'    => $position->warehouse_id,
                'alert_type'      => 'negative_stock',
                'current_quantity'=> $position->qty_on_hand,
                'threshold_quantity' => 0,
            ]);
        }
    }

    private function upsertAlert(array $data): StockAlert
    {
        return StockAlert::updateOrCreate(
            [
                'organization_id'    => $data['organization_id'],
                'product_id'         => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'warehouse_id'       => $data['warehouse_id'] ?? null,
                'alert_type'         => $data['alert_type'],
                'status'             => 'active',
            ],
            [
                'current_quantity'   => $data['current_quantity'] ?? null,
                'threshold_quantity' => $data['threshold_quantity'] ?? null,
                'expiry_date'        => $data['expiry_date'] ?? null,
            ]
        );
    }

    private function getCurrentAvailable(int $productId, ?int $variantId, ?int $warehouseId): float
    {
        return StockPosition::where('product_id', $productId)
            ->when($variantId,   fn ($q) => $q->where('product_variant_id', $variantId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->sum('qty_available');
    }

    private function daysOfSupply(int $productId, ?int $warehouseId): float
    {
        // Average daily usage over last 90 days
        $dailyUsage = \App\Models\StockLedgerEntry::where('product_id', $productId)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where('direction', 'OUT')
            ->where('movement_date', '>=', now()->subDays(90))
            ->whereIn('movement_type', ['sales_issue', 'production_consume'])
            ->sum('quantity') / 90;

        if ($dailyUsage <= 0) return PHP_INT_MAX;

        $available = $this->getCurrentAvailable($productId, null, $warehouseId);
        return $available / $dailyUsage;
    }

    private function calcEoq(int $productId): float
    {
        // Wilson EOQ formula: sqrt((2 * annual_demand * order_cost) / holding_cost_rate)
        $product = Product::find($productId);
        $annualDemand = \App\Models\StockLedgerEntry::where('product_id', $productId)
            ->where('direction', 'OUT')
            ->where('movement_date', '>=', now()->subYear())
            ->sum('quantity');

        $orderCost   = 50;   // default ordering cost in currency units
        $holdingRate = 0.25; // 25% of unit cost per year
        $unitCost    = $product?->standard_cost ?? 1;

        if ($unitCost <= 0 || $holdingRate <= 0) return 1;

        return ceil(sqrt((2 * $annualDemand * $orderCost) / ($unitCost * $holdingRate)));
    }

    private function calcDaysOfSupplyQty(ReorderRule $rule): float
    {
        $dailyUsage = \App\Models\StockLedgerEntry::where('product_id', $rule->product_id)
            ->where('direction', 'OUT')
            ->where('movement_date', '>=', now()->subDays(90))
            ->sum('quantity') / 90;

        return ceil($dailyUsage * ($rule->days_of_supply ?? 7));
    }
}

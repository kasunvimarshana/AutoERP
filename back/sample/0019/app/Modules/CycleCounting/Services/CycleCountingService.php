<?php

namespace App\Modules\CycleCounting\Services;

use App\Modules\CycleCounting\Models\CycleCountPlan;
use App\Modules\CycleCounting\Models\CycleCountSession;
use App\Modules\CycleCounting\Models\CycleCountItem;
use App\Modules\CycleCounting\Models\AbcClassification;
use App\Modules\CycleCounting\Models\InventoryDiscrepancy;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\StockMovement\Models\StockAdjustment;
use App\Modules\Audit\Services\AuditLedgerService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CycleCountingService
 *
 * Manages the full cycle counting workflow:
 *   1. ABC Classification — classify products by value/velocity/risk
 *   2. Plan Management — configure counting schedules and scope
 *   3. Session Generation — auto-generate count sessions per schedule
 *   4. Count Sheet Population — pull system quantities (blind or visible)
 *   5. Count Recording — capture counted quantities (with recount support)
 *   6. Variance Analysis — compare system vs counted; flag discrepancies
 *   7. Approval Workflow — manager review before adjustment
 *   8. Inventory Adjustment — post approved adjustments to stock
 *   9. Accuracy Reporting — KPI tracking per session / warehouse
 *
 * Supported methods: ABC | Periodic | Continuous | Location-Based |
 *                    Zero-Balance | Random | Discrepancy-Triggered
 */
class CycleCountingService
{
    public function __construct(
        protected AuditLedgerService $auditLedger,
    ) {}

    // ════════════════════════════════════════════════════════════════════════
    // ABC CLASSIFICATION
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Run ABC analysis for a warehouse.
     * Classifies products into A/B/C tiers based on annual usage value.
     * A = top 80% of value (typically ~20% of items)
     * B = next 15% of value
     * C = remaining 5% of value
     * Thresholds are configurable per warehouse.
     */
    public function runAbcClassification(
        int    $warehouseId,
        string $basis         = 'value',  // value | velocity | value_velocity | manual
        array  $thresholds    = ['A' => 80, 'B' => 95, 'C' => 100], // cumulative %
        string $tenantId      = null
    ): array {
        return DB::transaction(function () use ($warehouseId, $basis, $thresholds, $tenantId) {
            // Pull all products with stock in this warehouse
            $stockData = $this->getStockValueVelocity($warehouseId);

            if ($stockData->isEmpty()) {
                return ['classified' => 0, 'skipped' => 0];
            }

            // Compute annual usage value
            $totalValue   = $stockData->sum('annual_value');
            $classified   = 0;

            // Sort descending by chosen basis
            $sorted = match ($basis) {
                'velocity'        => $stockData->sortByDesc('velocity'),
                'value_velocity'  => $stockData->sortByDesc(fn($s) => $s->annual_value * $s->velocity),
                default           => $stockData->sortByDesc('annual_value'),
            };

            $cumulativeValue  = 0;
            $cumulativePct    = 0;

            foreach ($sorted as $item) {
                $cumulativeValue += $item->annual_value;
                $cumulativePct    = $totalValue > 0 ? ($cumulativeValue / $totalValue) * 100 : 0;

                $class = 'C';
                foreach ($thresholds as $cls => $maxPct) {
                    if ($cumulativePct <= $maxPct) { $class = $cls; break; }
                }

                // Determine counting frequency based on class
                $frequencyDays = match ($class) {
                    'A' => 30,    // Count A items monthly
                    'B' => 90,    // Count B items quarterly
                    'C' => 180,   // Count C items semi-annually
                    default => 365,
                };

                AbcClassification::updateOrCreate(
                    [
                        'product_id'  => $item->product_id,
                        'variant_id'  => $item->variant_id,
                        'warehouse_id' => $warehouseId,
                    ],
                    [
                        'tenant_id'              => $tenantId,
                        'abc_class'              => $class,
                        'classification_basis'   => $basis,
                        'annual_usage_value'     => $item->annual_value,
                        'usage_velocity'         => $item->velocity ?? 0,
                        'cumulative_pct'         => round($cumulativePct, 4),
                        'count_frequency_days'   => $frequencyDays,
                        'classified_date'        => today(),
                        'next_review_date'       => today()->addDays(90),
                    ]
                );

                $classified++;
            }

            return ['classified' => $classified, 'total_value' => $totalValue, 'basis' => $basis];
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    // SESSION GENERATION
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Generate a count session from a plan.
     * Populates count items based on plan scope (ABC class, category, location).
     */
    public function generateSession(CycleCountPlan $plan, ?Carbon $scheduledDate = null): CycleCountSession
    {
        return DB::transaction(function () use ($plan, $scheduledDate) {
            $date = $scheduledDate ?? now();

            $session = CycleCountSession::create([
                'tenant_id'        => $plan->tenant_id,
                'plan_id'          => $plan->id,
                'warehouse_id'     => $plan->warehouse_id,
                'organization_id'  => $plan->organization_id,
                'session_number'   => $this->generateSessionNumber($plan),
                'session_type'     => 'cycle_count',
                'status'           => 'draft',
                'scheduled_date'   => $date,
            ]);

            // Build count items from current stock levels
            $stockItems = $this->resolveItemsForSession($plan);

            $itemCount = 0;
            foreach ($stockItems as $stock) {
                CycleCountItem::create([
                    'session_id'        => $session->id,
                    'product_id'        => $stock->product_id,
                    'variant_id'        => $stock->variant_id,
                    'warehouse_id'      => $stock->warehouse_id,
                    'location_id'       => $stock->location_id,
                    'lot_id'            => $stock->lot_id,
                    'uom_id'            => $stock->uom_id,
                    // Blind count: don't populate system_qty if blind_count=true
                    'system_qty'        => $plan->blind_count ? 0 : $stock->qty_on_hand,
                    'system_unit_cost'  => $stock->unit_cost,
                    'system_total_value' => $stock->qty_on_hand * $stock->unit_cost,
                    // Store actual system qty for comparison after count
                    // (hidden in blind mode, retrieved after count is submitted)
                    'status'            => 'pending',
                ]);
                $itemCount++;
            }

            $session->update(['total_items_to_count' => $itemCount]);
            $plan->update(['last_run_at' => now(), 'next_run_at' => $this->calculateNextRun($plan)]);

            return $session;
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    // COUNT RECORDING
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Record counted quantities for a list of count items.
     * Computes variance and flags items needing recount.
     */
    public function recordCounts(CycleCountSession $session, array $counts, int $countedBy, bool $isSecondCount = false): array
    {
        return DB::transaction(function () use ($session, $counts, $countedBy, $isSecondCount) {
            $varianceItems = 0;
            $totalVarianceValue = 0;

            foreach ($counts as $countData) {
                $item = CycleCountItem::where('session_id', $session->id)
                    ->where('id', $countData['item_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $countedQty = (float) $countData['counted_qty'];

                // Store actual system qty for variance calculation
                $actualSystemQty = $this->getActualSystemQty($item);

                if ($isSecondCount) {
                    $item->counted_qty_2  = $countedQty;
                    $item->counted_by_2   = $countedBy;
                    $item->counted_at_2   = now();
                } else {
                    $item->counted_qty  = $countedQty;
                    $item->counted_by   = $countedBy;
                    $item->counted_at   = now();
                }

                // Use reconciled qty if both counts are done
                $reconciled = $this->reconcileCounts($item, $countedQty, $isSecondCount);
                $item->reconciled_qty  = $reconciled;
                $item->system_qty      = $actualSystemQty; // Reveal system qty now

                // Compute variance
                $varianceQty   = $reconciled - $actualSystemQty;
                $varianceValue = $varianceQty * $item->system_unit_cost;
                $variancePct   = $actualSystemQty != 0 ? abs($varianceQty / $actualSystemQty) * 100 : 0;

                $item->variance_qty   = $varianceQty;
                $item->variance_value = $varianceValue;
                $item->variance_pct   = $variancePct;

                // Check if recount is required
                $plan = $session->plan;
                $requiresRecount = $plan && $plan->require_recount_on_variance
                    && abs($variancePct) > ($plan->variance_threshold_pct ?? 2)
                    && ! $isSecondCount
                    && $item->recount_number === 0;

                $item->requires_recount = $requiresRecount;

                if (abs($varianceQty) > 0.001) {
                    $varianceItems++;
                    $totalVarianceValue += abs($varianceValue);
                }

                $item->status = $requiresRecount ? 'recount_required' : 'counted';

                // Log count history
                $item->history()->create([
                    'attempt_number' => $isSecondCount ? 2 : (1 + $item->recount_number),
                    'counted_qty'    => $countedQty,
                    'counted_by'     => $countedBy,
                    'counted_at'     => now(),
                    'notes'          => $countData['notes'] ?? null,
                ]);

                $item->save();
            }

            // Update session summary
            $session->increment('total_items_counted', count($counts));
            $session->update([
                'items_with_variance'  => $varianceItems,
                'total_variance_value' => DB::raw("total_variance_value + $totalVarianceValue"),
            ]);

            return [
                'recorded'       => count($counts),
                'with_variance'  => $varianceItems,
                'recount_needed' => CycleCountItem::where('session_id', $session->id)->where('requires_recount', true)->count(),
            ];
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    // APPROVAL & ADJUSTMENT
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Approve a cycle count session and post inventory adjustments.
     */
    public function approveAndAdjust(CycleCountSession $session, int $approvedBy, ?string $notes = null): StockAdjustment
    {
        return DB::transaction(function () use ($session, $approvedBy, $notes) {
            // Create a stock adjustment record
            $adjustment = StockAdjustment::create([
                'tenant_id'               => $session->tenant_id,
                'organization_id'         => $session->organization_id,
                'warehouse_id'            => $session->warehouse_id,
                'reference_number'        => 'CC-ADJ-' . $session->session_number,
                'adjustment_type'         => 'cycle_count',
                'status'                  => 'draft',
                'adjustment_date'         => now(),
                'cycle_count_session_id'  => $session->id,
                'approved'                => true,
                'approved_by'             => $approvedBy,
                'approved_at'             => now(),
                'notes'                   => $notes,
            ]);

            $totalValueImpact = 0;
            $discrepanciesCreated = 0;

            foreach ($session->items()->where('variance_qty', '!=', 0)->get() as $item) {
                if (abs($item->variance_qty) < 0.0001) continue;

                // Post inventory adjustment for this variance
                $this->applyInventoryAdjustment($item, $adjustment);
                $totalValueImpact += abs($item->variance_value);

                // Create discrepancy record for significant variances
                if (abs($item->variance_pct) > 1.0 || abs($item->variance_value) > 100) {
                    $this->createDiscrepancyRecord($item, $session, $adjustment);
                    $discrepanciesCreated++;
                }

                $item->update(['status' => 'adjusted']);

                // Write audit ledger
                $this->auditLedger->writeCycleCountAdjustment($item, $adjustment);
            }

            $session->update([
                'status'                 => 'adjusted',
                'adjusted_at'            => now(),
                'adjusted_by'            => $approvedBy,
                'stock_adjustment_id'    => $adjustment->id,
                'accuracy_rate_pct'      => $this->computeAccuracyRate($session),
            ]);

            $adjustment->update(['status' => 'done', 'total_value_impact' => $totalValueImpact]);

            return $adjustment;
        });
    }

    protected function applyInventoryAdjustment(CycleCountItem $item, StockAdjustment $adjustment): void
    {
        $delta = $item->reconciled_qty - $item->system_qty;

        StockLevel::updateOrCreate(
            [
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
                'warehouse_id' => $item->warehouse_id,
                'location_id'  => $item->location_id,
                'lot_id'       => $item->lot_id,
                'uom_id'       => $item->uom_id,
            ],
            ['qty_on_hand' => 0, 'qty_available' => 0]
        )->increment('qty_on_hand', $delta, [
            'qty_available' => DB::raw("qty_available + ($delta)"),
            'total_value'   => DB::raw("(qty_on_hand + $delta) * unit_cost"),
        ]);
    }

    protected function createDiscrepancyRecord(CycleCountItem $item, CycleCountSession $session, StockAdjustment $adjustment): void
    {
        InventoryDiscrepancy::create([
            'tenant_id'        => $session->tenant_id,
            'session_id'       => $session->id,
            'count_item_id'    => $item->id,
            'warehouse_id'     => $item->warehouse_id,
            'product_id'       => $item->product_id,
            'variant_id'       => $item->variant_id,
            'lot_id'           => $item->lot_id,
            'location_id'      => $item->location_id,
            'uom_id'           => $item->uom_id,
            'system_qty'       => $item->system_qty,
            'counted_qty'      => $item->reconciled_qty,
            'variance_qty'     => $item->variance_qty,
            'variance_value'   => $item->variance_value,
            'unit_cost'        => $item->system_unit_cost,
            'discrepancy_type' => $item->variance_qty > 0 ? 'over' : 'short',
            'status'           => 'open',
            'adjustment_id'    => $adjustment->id,
        ]);
    }

    protected function reconcileCounts(CycleCountItem $item, float $newQty, bool $isSecond): float
    {
        if (! $isSecond || $item->counted_qty === null) return $newQty;

        // If both counts within tolerance (2%), use average; else flag
        $firstQty  = (float) $item->counted_qty;
        $diff      = abs($firstQty - $newQty);
        $tolerance = max($firstQty, $newQty) > 0 ? $diff / max($firstQty, $newQty) : 0;

        return $tolerance <= 0.02 ? ($firstQty + $newQty) / 2 : $newQty; // Use second count if outside tolerance
    }

    protected function getActualSystemQty(CycleCountItem $item): float
    {
        return (float) StockLevel::where([
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'warehouse_id' => $item->warehouse_id,
            'location_id'  => $item->location_id,
            'lot_id'       => $item->lot_id,
        ])->value('qty_on_hand') ?? 0;
    }

    protected function resolveItemsForSession(CycleCountPlan $plan)
    {
        $query = StockLevel::query()
            ->where('stock_levels.warehouse_id', $plan->warehouse_id)
            ->select('stock_levels.*');

        // ABC filter
        if (! empty($plan->included_abc_classes)) {
            $query->join('abc_classifications', function ($j) use ($plan) {
                $j->on('abc_classifications.product_id', '=', 'stock_levels.product_id')
                  ->on('abc_classifications.warehouse_id', '=', 'stock_levels.warehouse_id')
                  ->whereIn('abc_classifications.abc_class', $plan->included_abc_classes);
            });
        }

        // Location filter
        if (! empty($plan->included_locations)) {
            $query->whereIn('stock_levels.location_id', $plan->included_locations);
        }
        if (! empty($plan->excluded_locations)) {
            $query->whereNotIn('stock_levels.location_id', $plan->excluded_locations);
        }

        // Zero-balance method: only items with system qty = 0
        if ($plan->count_method === 'zero_balance') {
            $query->where('stock_levels.qty_on_hand', 0);
        }

        return $query->get();
    }

    protected function computeAccuracyRate(CycleCountSession $session): float
    {
        $total    = $session->items()->count();
        $accurate = $session->items()->whereRaw('ABS(variance_pct) <= 1')->count();
        return $total > 0 ? round(($accurate / $total) * 100, 2) : 100;
    }

    protected function getStockValueVelocity(int $warehouseId)
    {
        return StockLevel::where('warehouse_id', $warehouseId)
            ->selectRaw('
                product_id,
                variant_id,
                SUM(qty_on_hand * unit_cost) as annual_value,
                COUNT(*) as velocity
            ')
            ->groupBy('product_id', 'variant_id')
            ->get();
    }

    protected function generateSessionNumber(CycleCountPlan $plan): string
    {
        $count = CycleCountSession::where('plan_id', $plan->id)->count() + 1;
        return 'CC-' . $plan->warehouse_id . '-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    protected function calculateNextRun(CycleCountPlan $plan): Carbon
    {
        return match ($plan->frequency) {
            'daily'     => now()->addDay(),
            'weekly'    => now()->addWeek(),
            'biweekly'  => now()->addWeeks(2),
            'monthly'   => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'annual'    => now()->addYear(),
            default     => now()->addMonth(),
        };
    }
}

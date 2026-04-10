<?php

<?php

namespace App\Services\Inventory;

use App\Models\CycleCountSchedule;
use App\Models\CycleCountItem;
use App\Models\InventoryStock;
use App\Models\InventoryAdjustment;
use Illuminate\Support\Facades\DB;

class CycleCountService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Generate count items based on schedule method.
     */
    public function generateCountItems($scheduleId)
    {
        $schedule = CycleCountSchedule::findOrFail($scheduleId);
        
        $query = InventoryStock::where('warehouse_id', $schedule->warehouse_id)
            ->where('quantity', '>', 0);

        // Apply filters (e.g., by product category, location)
        if ($filters = $schedule->filters) {
            if (isset($filters['product_category'])) {
                $query->whereHas('product', fn($q) => $q->where('category', $filters['product_category']));
            }
            if (isset($filters['location_zone'])) {
                $query->whereHas('location', fn($q) => $q->where('zone', $filters['location_zone']));
            }
        }

        $items = $query->get();

        // Apply method
        switch ($schedule->method) {
            case 'periodic':
                // All items in scope are counted
                $selected = $items;
                break;
            case 'continuous':
                // Count items that have not been counted in X days (rolling)
                $selected = $items->filter(fn($item) => 
                    !CycleCountItem::where('product_id', $item->product_id)
                        ->where('warehouse_id', $item->warehouse_id)
                        ->where('batch_id', $item->batch_id)
                        ->where('counted_at', '>', now()->subDays($schedule->days_between_counts ?? 30))
                        ->exists()
                );
                break;
            case 'abc_based':
                // Fetch product ABC classification (from product.abc_class or dynamic calculation)
                $thresholds = $schedule->abc_class_thresholds ?? ['A' => 0.8, 'B' => 0.15, 'C' => 0.05];
                // Group items by product and calculate cumulative value
                $productValues = $items->groupBy('product_id')->map(fn($group) => 
                    $group->sum(fn($s) => $s->quantity * $s->unit_cost)
                )->sortDesc();
                $totalValue = $productValues->sum();
                $cumulative = 0;
                $selected = collect();
                foreach ($productValues as $productId => $value) {
                    $cumulative += $value / $totalValue;
                    $class = $cumulative <= $thresholds['A'] ? 'A' : ($cumulative <= $thresholds['A'] + $thresholds['B'] ? 'B' : 'C');
                    // For A count all, for B count 20%, for C count 5% (configurable)
                    $percentage = match($class) {
                        'A' => 1.0,
                        'B' => 0.2,
                        'C' => 0.05,
                        default => 0.05,
                    };
                    // Sample items for this product
                    $productItems = $items->where('product_id', $productId);
                    $sampleSize = max(1, ceil($productItems->count() * $percentage));
                    $selected = $selected->concat($productItems->random($sampleSize));
                }
                break;
            case 'random_sampling':
                $percentage = $schedule->sample_percentage ?? 5;
                $selected = $items->random(ceil($items->count() * $percentage / 100));
                break;
            default:
                throw new \Exception("Unsupported cycle count method");
        }

        // Create cycle count items
        foreach ($selected as $stock) {
            CycleCountItem::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'product_id' => $stock->product_id,
                    'variant_id' => $stock->variant_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'location_id' => $stock->location_id,
                    'batch_id' => $stock->batch_id,
                    'serial_id' => $stock->serial_id,
                ],
                [
                    'expected_quantity' => $stock->quantity,
                    'status' => 'pending',
                ]
            );
        }

        $schedule->update(['last_run_at' => now(), 'next_run_at' => $this->calculateNextRun($schedule)]);
        return $selected->count();
    }

    /**
     * Record a count for a specific item.
     */
    public function recordCount($countItemId, $countedQuantity, $userId, $notes = null)
    {
        $item = CycleCountItem::findOrFail($countItemId);
        $item->update([
            'counted_quantity' => $countedQuantity,
            'status' => 'counted',
            'counted_by' => $userId,
            'counted_at' => now(),
            'notes' => $notes,
        ]);

        // If variance exceeds tolerance, mark for verification
        $variance = abs($countedQuantity - $item->expected_quantity);
        $tolerance = config('inventory.cycle_count_tolerance', 0.05);
        if ($variance > $item->expected_quantity * $tolerance) {
            $item->status = 'counted'; // still counted, but will show as discrepancy
        }

        return $item;
    }

    /**
     * Verify and apply adjustments for a count item.
     */
    public function verifyAndAdjust($countItemId, $userId, $applyAdjustment = true)
    {
        $item = CycleCountItem::findOrFail($countItemId);
        $item->status = 'verified';
        $item->verified_by = $userId;
        $item->verified_at = now();
        $item->save();

        if ($applyAdjustment && $item->counted_quantity != $item->expected_quantity) {
            // Create an inventory adjustment
            $difference = $item->counted_quantity - $item->expected_quantity;
            $this->inventoryService->adjustStock(
                $item->product_id,
                $item->warehouse_id,
                $difference,
                'cycle_count',
                $item->id,
                $item->location_id,
                $item->batch_id,
                $item->serial_id,
                'Cycle count adjustment',
                $userId
            );

            $item->adjustment_details = [
                'adjustment_made' => true,
                'adjusted_quantity' => $difference,
                'adjusted_at' => now(),
                'adjusted_by' => $userId,
            ];
            $item->status = 'adjusted';
            $item->save();
        }

        return $item;
    }

    protected function calculateNextRun($schedule)
    {
        $last = $schedule->last_run_at;
        if (!$last) return null;
        switch ($schedule->frequency) {
            case 'daily': return $last->addDay();
            case 'weekly': return $last->addWeek();
            case 'monthly': return $last->addMonth();
            case 'quarterly': return $last->addQuarter();
            case 'yearly': return $last->addYear();
            default: return null;
        }
    }
}
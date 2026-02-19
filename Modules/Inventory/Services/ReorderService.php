<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Modules\Core\Helpers\MathHelper;
use Modules\Inventory\Events\ReorderPointReached;
use Modules\Inventory\Repositories\StockItemRepository;

/**
 * Reorder Service
 *
 * Analyzes stock levels, generates reorder suggestions, checks reorder points,
 * and provides inventory planning insights.
 */
class ReorderService
{
    public function __construct(
        private StockItemRepository $stockItemRepository
    ) {}

    /**
     * Analyze stock levels and generate reorder suggestions.
     *
     * @param  array  $filters  Optional filters (warehouse_id, product_id, etc.)
     * @return Collection Reorder suggestions
     */
    public function generateReorderSuggestions(array $filters = []): Collection
    {
        $stockItems = $this->getItemsRequiringReorder($filters);

        $suggestions = [];

        foreach ($stockItems as $stockItem) {
            if (! $this->shouldReorder($stockItem)) {
                continue;
            }

            $suggestions[] = [
                'product_id' => $stockItem->product_id,
                'product_name' => $stockItem->product->name ?? null,
                'product_sku' => $stockItem->product->sku ?? null,
                'warehouse_id' => $stockItem->warehouse_id,
                'warehouse_name' => $stockItem->warehouse->name ?? null,
                'current_quantity' => $stockItem->quantity,
                'available_quantity' => $stockItem->available_quantity,
                'reserved_quantity' => $stockItem->reserved_quantity,
                'reorder_point' => $stockItem->reorder_point,
                'reorder_quantity' => $stockItem->reorder_quantity,
                'suggested_order_quantity' => $this->calculateSuggestedOrderQuantity($stockItem),
                'days_to_stockout' => $this->estimateDaysToStockout($stockItem),
                'priority' => $this->calculatePriority($stockItem),
                'estimated_cost' => $this->estimateReorderCost($stockItem),
            ];
        }

        // Sort by priority (highest first)
        usort($suggestions, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return collect($suggestions);
    }

    /**
     * Check if specific product in warehouse needs reorder.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     */
    public function needsReorder(string $productId, string $warehouseId): bool
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        return $stockItem ? $this->shouldReorder($stockItem) : false;
    }

    /**
     * Get reorder details for a specific product in warehouse.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @return array|null Reorder details or null if no reorder needed
     */
    public function getReorderDetails(string $productId, string $warehouseId): ?array
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem || ! $this->shouldReorder($stockItem)) {
            return null;
        }

        return [
            'current_quantity' => $stockItem->quantity,
            'available_quantity' => $stockItem->available_quantity,
            'reserved_quantity' => $stockItem->reserved_quantity,
            'reorder_point' => $stockItem->reorder_point,
            'reorder_quantity' => $stockItem->reorder_quantity,
            'suggested_order_quantity' => $this->calculateSuggestedOrderQuantity($stockItem),
            'minimum_quantity' => $stockItem->minimum_quantity,
            'maximum_quantity' => $stockItem->maximum_quantity,
            'days_to_stockout' => $this->estimateDaysToStockout($stockItem),
            'priority' => $this->calculatePriority($stockItem),
            'estimated_cost' => $this->estimateReorderCost($stockItem),
            'average_cost' => $stockItem->average_cost,
        ];
    }

    /**
     * Check reorder points for all items and fire events.
     *
     * @return int Number of items at or below reorder point
     */
    public function checkReorderPoints(): int
    {
        $lowStockItems = $this->stockItemRepository->getLowStockItems(1000);
        $count = 0;

        foreach ($lowStockItems->items() as $stockItem) {
            if ($this->shouldReorder($stockItem)) {
                event(new ReorderPointReached(
                    $stockItem->product_id,
                    $stockItem->warehouse_id,
                    $stockItem->available_quantity,
                    $stockItem->reorder_point
                ));
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get items requiring reorder with filters.
     */
    private function getItemsRequiringReorder(array $filters = []): Collection
    {
        $query = $this->stockItemRepository->model()
            ->whereNotNull('reorder_point')
            ->whereRaw('CAST(available_quantity AS DECIMAL(10,2)) <= CAST(reorder_point AS DECIMAL(10,2))')
            ->with(['product', 'warehouse']);

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        return $query->get();
    }

    /**
     * Determine if stock item should be reordered.
     */
    private function shouldReorder($stockItem): bool
    {
        // Must have reorder point set
        if ($stockItem->reorder_point === null) {
            return false;
        }

        // Check if available quantity is at or below reorder point
        if (! MathHelper::lessThan($stockItem->available_quantity, $stockItem->reorder_point) &&
            ! MathHelper::equals($stockItem->available_quantity, $stockItem->reorder_point)) {
            return false;
        }

        // Don't reorder if already at maximum quantity
        if ($stockItem->maximum_quantity !== null &&
            MathHelper::greaterThan($stockItem->quantity, $stockItem->maximum_quantity)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate suggested order quantity.
     */
    private function calculateSuggestedOrderQuantity($stockItem): string
    {
        // Use reorder quantity if set
        if ($stockItem->reorder_quantity !== null && MathHelper::greaterThan($stockItem->reorder_quantity, '0')) {
            return $stockItem->reorder_quantity;
        }

        // Calculate to reach maximum or reasonable level
        if ($stockItem->maximum_quantity !== null) {
            $deficit = MathHelper::subtract($stockItem->maximum_quantity, $stockItem->available_quantity);

            return MathHelper::max($deficit, '1');
        }

        // Default to double the reorder point minus current available
        if ($stockItem->reorder_point !== null) {
            $targetLevel = MathHelper::multiply($stockItem->reorder_point, '2');
            $orderQty = MathHelper::subtract($targetLevel, $stockItem->available_quantity);

            return MathHelper::max($orderQty, '1');
        }

        return '1';
    }

    /**
     * Estimate days until stockout based on usage rate.
     *
     * Analyzes current available quantity and average daily usage
     * to estimate when stock will run out.
     */
    private function estimateDaysToStockout($stockItem): ?int
    {
        $availableQty = (float) $stockItem->available_quantity;

        if ($availableQty <= 0) {
            return 0;
        }

        // Get average daily usage from actual stock movements
        $averageDailyUsage = $this->calculateAverageDailyUsage($stockItem);

        if ($averageDailyUsage <= 0) {
            return null; // Cannot estimate without usage data
        }

        return (int) ceil($availableQty / $averageDailyUsage);
    }

    /**
     * Calculate priority score (1-10, higher is more urgent).
     */
    private function calculatePriority($stockItem): int
    {
        $availableQty = (float) $stockItem->available_quantity;
        $reorderPoint = (float) $stockItem->reorder_point;

        // Out of stock = highest priority
        if ($availableQty <= 0) {
            return 10;
        }

        // Calculate how far below reorder point
        if ($reorderPoint > 0) {
            $percentBelowReorder = (($reorderPoint - $availableQty) / $reorderPoint) * 100;

            if ($percentBelowReorder >= 75) {
                return 9;
            } elseif ($percentBelowReorder >= 50) {
                return 7;
            } elseif ($percentBelowReorder >= 25) {
                return 5;
            } else {
                return 3;
            }
        }

        return 1;
    }

    /**
     * Estimate cost of reorder.
     */
    private function estimateReorderCost($stockItem): string
    {
        $suggestedQty = $this->calculateSuggestedOrderQuantity($stockItem);
        $averageCost = $stockItem->average_cost ?? '0';

        return MathHelper::multiply($suggestedQty, $averageCost);
    }

    /**
     * Calculate average daily usage based on actual stock movements.
     *
     * Analyzes outbound stock movements (issues, sales) from the last 30 days
     * to determine the average daily usage rate.
     */
    private function calculateAverageDailyUsage($stockItem): float
    {
        // Get stock movements for the last 30 days
        $days = 30;
        $startDate = now()->subDays($days);

        // Query outbound movements (issue type) for this stock item
        $totalIssued = \Modules\Inventory\Models\StockMovement::query()
            ->where('stock_item_id', $stockItem->id)
            ->where('type', \Modules\Inventory\Enums\StockMovementType::Issue)
            ->where('created_at', '>=', $startDate)
            ->sum('quantity');

        // Convert to float and calculate daily average
        $totalIssuedQty = (float) $totalIssued;

        if ($totalIssuedQty > 0) {
            return $totalIssuedQty / $days;
        }

        // Fallback: If no movement data, estimate based on reorder point
        // Assuming reorder point represents approximately 7 days of usage
        if ($stockItem->reorder_point !== null && (float) $stockItem->reorder_point > 0) {
            return (float) $stockItem->reorder_point / 7;
        }

        return 0;
    }

    /**
     * Get reorder summary by warehouse.
     *
     * @param  string|null  $warehouseId  Optional warehouse filter
     * @return array Summary statistics
     */
    public function getReorderSummary(?string $warehouseId = null): array
    {
        $filters = $warehouseId ? ['warehouse_id' => $warehouseId] : [];
        $suggestions = $this->generateReorderSuggestions($filters);

        $totalEstimatedCost = '0';
        $priorityCounts = array_fill(1, 10, 0);

        foreach ($suggestions as $suggestion) {
            $totalEstimatedCost = MathHelper::add(
                $totalEstimatedCost,
                $suggestion['estimated_cost'] ?? '0'
            );

            $priority = $suggestion['priority'] ?? 1;
            $priorityCounts[$priority]++;
        }

        return [
            'total_items_needing_reorder' => $suggestions->count(),
            'critical_items' => $priorityCounts[10] + $priorityCounts[9],
            'high_priority_items' => $priorityCounts[8] + $priorityCounts[7],
            'medium_priority_items' => $priorityCounts[6] + $priorityCounts[5],
            'low_priority_items' => $priorityCounts[4] + $priorityCounts[3] + $priorityCounts[2] + $priorityCounts[1],
            'total_estimated_cost' => $totalEstimatedCost,
        ];
    }
}

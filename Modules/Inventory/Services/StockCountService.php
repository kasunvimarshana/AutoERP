<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Events\StockCountCancelled;
use Modules\Inventory\Events\StockCountCompleted;
use Modules\Inventory\Events\StockCountReconciled;
use Modules\Inventory\Events\StockCountStarted;
use Modules\Inventory\Exceptions\InvalidStockCountException;
use Modules\Inventory\Models\StockCount;
use Modules\Inventory\Repositories\StockCountRepository;
use Modules\Inventory\Repositories\StockItemRepository;

/**
 * Stock Count Service
 *
 * Manages stock count lifecycle including creation, starting, completing,
 * reconciliation, and cancellation. Calculates variances and creates
 * automatic adjustments.
 */
class StockCountService
{
    public function __construct(
        private StockCountRepository $stockCountRepository,
        private StockItemRepository $stockItemRepository,
        private StockMovementService $stockMovementService,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new stock count.
     *
     * @param  array  $data  Stock count data
     * @param  array  $items  Stock count items
     */
    public function create(array $data, array $items = []): StockCount
    {
        return TransactionHelper::execute(function () use ($data, $items) {
            // Generate count number if not provided
            if (empty($data['count_number'])) {
                $data['count_number'] = $this->generateCountNumber();
            }

            // Set default status and dates
            $data['status'] = StockCountStatus::PLANNED;
            $data['count_date'] = $data['count_date'] ?? now();

            // Create stock count
            $stockCount = $this->stockCountRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $this->addItemToCount($stockCount, $item);
                }
                $stockCount->load('items');
            }

            return $stockCount;
        });
    }

    /**
     * Start stock count (transition from planned to in progress).
     *
     * @param  string  $id  Stock count ID
     */
    public function start(string $id): StockCount
    {
        $stockCount = $this->stockCountRepository->findOrFail($id);

        if (! $stockCount->canStart()) {
            throw new InvalidStockCountException(
                "Stock count cannot be started in {$stockCount->status->value} status"
            );
        }

        // Validate count has items
        if ($stockCount->items()->count() === 0) {
            throw new InvalidStockCountException(
                'Stock count must have at least one item to start'
            );
        }

        // Capture current system quantities
        foreach ($stockCount->items as $item) {
            $stockItem = $this->stockItemRepository->findByProductAndWarehouse(
                $item->product_id,
                $stockCount->warehouse_id
            );

            $item->update([
                'system_quantity' => $stockItem ? $stockItem->quantity : '0',
            ]);
        }

        $stockCount = $this->stockCountRepository->updateAndReturn($stockCount->id, [
            'status' => StockCountStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);

        // Fire event
        event(new StockCountStarted($stockCount));

        return $stockCount;
    }

    /**
     * Complete stock count (transition from in progress to completed).
     *
     * @param  string  $id  Stock count ID
     */
    public function complete(string $id): StockCount
    {
        $stockCount = $this->stockCountRepository->findOrFail($id);

        if (! $stockCount->canComplete()) {
            throw new InvalidStockCountException(
                "Stock count cannot be completed in {$stockCount->status->value} status"
            );
        }

        // Validate all items have been counted
        $unCountedItems = $stockCount->items()->whereNull('counted_quantity')->count();
        if ($unCountedItems > 0) {
            throw new InvalidStockCountException(
                "All items must be counted before completion. {$unCountedItems} items remain uncounted."
            );
        }

        // Calculate variances
        foreach ($stockCount->items as $item) {
            $this->calculateItemVariance($item);
        }

        $stockCount = $this->stockCountRepository->updateAndReturn($stockCount->id, [
            'status' => StockCountStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        // Fire event
        event(new StockCountCompleted($stockCount));

        return $stockCount;
    }

    /**
     * Reconcile stock count (create adjustments for variances).
     *
     * @param  string  $id  Stock count ID
     * @param  bool  $autoAdjust  Whether to automatically create adjustments
     */
    public function reconcile(string $id, bool $autoAdjust = true): StockCount
    {
        return TransactionHelper::execute(function () use ($id, $autoAdjust) {
            $stockCount = $this->stockCountRepository->findOrFail($id);

            if (! $stockCount->canReconcile()) {
                throw new InvalidStockCountException(
                    "Stock count cannot be reconciled in {$stockCount->status->value} status"
                );
            }

            // Create adjustments for items with variances
            if ($autoAdjust) {
                $this->createAdjustmentsForVariances($stockCount);
            }

            $stockCount = $this->stockCountRepository->updateAndReturn($stockCount->id, [
                'status' => StockCountStatus::RECONCILED,
                'reconciled_at' => now(),
            ]);

            // Fire event
            event(new StockCountReconciled($stockCount));

            return $stockCount;
        });
    }

    /**
     * Cancel stock count.
     *
     * @param  string  $id  Stock count ID
     * @param  string|null  $reason  Cancellation reason
     */
    public function cancel(string $id, ?string $reason = null): StockCount
    {
        $stockCount = $this->stockCountRepository->findOrFail($id);

        if (! $stockCount->canCancel()) {
            throw new InvalidStockCountException(
                "Stock count cannot be cancelled in {$stockCount->status->value} status"
            );
        }

        $notes = $stockCount->notes;
        if ($reason) {
            $notes = $notes
                ? $notes."\n\nCancellation reason: {$reason}"
                : "Cancellation reason: {$reason}";
        }

        $updateData = [
            'status' => StockCountStatus::CANCELLED,
            'notes' => $notes,
        ];

        $stockCount = $this->stockCountRepository->updateAndReturn($stockCount->id, $updateData);

        // Fire event
        event(new StockCountCancelled($stockCount));

        return $stockCount;
    }

    /**
     * Update counted quantity for a stock count item.
     *
     * @param  string  $stockCountId  Stock count ID
     * @param  string  $itemId  Stock count item ID
     * @param  string  $countedQuantity  Counted quantity
     */
    public function updateItemCount(string $stockCountId, string $itemId, string $countedQuantity): void
    {
        $stockCount = $this->stockCountRepository->findOrFail($stockCountId);

        if (! $stockCount->canModify()) {
            throw new InvalidStockCountException(
                "Stock count items cannot be modified in {$stockCount->status->value} status"
            );
        }

        $item = $stockCount->items()->findOrFail($itemId);

        $item->update([
            'counted_quantity' => $countedQuantity,
        ]);

        // Calculate variance
        $this->calculateItemVariance($item);
    }

    /**
     * Add item to stock count.
     */
    private function addItemToCount(StockCount $stockCount, array $itemData): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse(
            $itemData['product_id'],
            $stockCount->warehouse_id
        );

        $stockCount->items()->create([
            'product_id' => $itemData['product_id'],
            'location_id' => $itemData['location_id'] ?? null,
            'system_quantity' => $stockItem ? $stockItem->quantity : '0',
            'counted_quantity' => $itemData['counted_quantity'] ?? null,
            'unit_cost' => $stockItem ? $stockItem->average_cost : '0',
            'notes' => $itemData['notes'] ?? null,
        ]);
    }

    /**
     * Calculate variance for stock count item.
     *
     * Calculates the difference between counted quantity and system quantity,
     * and determines the monetary value of the variance using BCMath precision.
     *
     * @param  mixed  $item  Stock count item model with system_quantity, counted_quantity, and unit_cost
     * @return void Updates the item with variance and variance_value
     */
    private function calculateItemVariance($item): void
    {
        $systemQty = $item->system_quantity ?? '0';
        $countedQty = $item->counted_quantity ?? '0';

        $variance = MathHelper::subtract($countedQty, $systemQty);
        $unitCost = $item->unit_cost ?? '0';
        $varianceValue = MathHelper::multiply($variance, $unitCost);

        $item->update([
            'variance' => $variance,
            'variance_value' => $varianceValue,
        ]);
    }

    /**
     * Create stock adjustments for items with variances.
     */
    private function createAdjustmentsForVariances(StockCount $stockCount): void
    {
        foreach ($stockCount->items as $item) {
            $variance = $item->variance ?? '0';

            // Skip items with no variance
            if (MathHelper::equals($variance, '0')) {
                continue;
            }

            // Create adjustment through StockMovementService
            $this->stockMovementService->processAdjustment([
                'tenant_id' => $stockCount->tenant_id,
                'organization_id' => $stockCount->warehouse->organization_id ?? null,
                'product_id' => $item->product_id,
                'warehouse_id' => $stockCount->warehouse_id,
                'adjustment_quantity' => $variance,
                'unit_cost' => $item->unit_cost,
                'reference_type' => 'stock_count',
                'reference_id' => $stockCount->id,
                'reference_number' => $stockCount->count_number,
                'movement_date' => now(),
                'notes' => "Adjustment from stock count {$stockCount->count_number}",
                'created_by' => $stockCount->approved_by ?? $stockCount->created_by,
            ]);
        }
    }

    /**
     * Generate unique count number.
     */
    private function generateCountNumber(): string
    {
        $prefix = config('inventory.stock_count.code_prefix', 'SC-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->stockCountRepository->findByCountNumber($code) !== null
        );
    }

    /**
     * Get variance summary for a stock count.
     *
     * @param  string  $id  Stock count ID
     * @return array Variance summary
     */
    public function getVarianceSummary(string $id): array
    {
        $stockCount = $this->stockCountRepository->findOrFail($id);

        $totalItems = $stockCount->items()->count();
        $countedItems = $stockCount->items()->whereNotNull('counted_quantity')->count();
        $varianceItems = $stockCount->getItemsWithVariances()->count();

        $totalVarianceValue = $stockCount->getTotalVarianceValue();
        $positiveVariances = '0';
        $negativeVariances = '0';

        foreach ($stockCount->items as $item) {
            $varianceValue = $item->variance_value ?? '0';
            if (MathHelper::greaterThan($varianceValue, '0')) {
                $positiveVariances = MathHelper::add($positiveVariances, $varianceValue);
            } elseif (MathHelper::lessThan($varianceValue, '0')) {
                $negativeVariances = MathHelper::add($negativeVariances, MathHelper::abs($varianceValue));
            }
        }

        return [
            'total_items' => $totalItems,
            'counted_items' => $countedItems,
            'uncounted_items' => $totalItems - $countedItems,
            'variance_items' => $varianceItems,
            'total_variance_value' => $totalVarianceValue,
            'positive_variances' => $positiveVariances,
            'negative_variances' => $negativeVariances,
            'completion_percentage' => $totalItems > 0 ? round(($countedItems / $totalItems) * 100, 2) : 0,
        ];
    }
}

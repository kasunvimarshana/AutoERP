<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Events\StockValueChanged;
use Modules\Inventory\Repositories\StockItemRepository;
use Modules\Inventory\Repositories\StockMovementRepository;

/**
 * Inventory Valuation Service
 *
 * Calculates stock values using different valuation methods (FIFO, LIFO,
 * Weighted Average, Standard Cost). Uses BCMath for precision-safe calculations.
 */
class InventoryValuationService
{
    public function __construct(
        private StockItemRepository $stockItemRepository,
        private StockMovementRepository $stockMovementRepository
    ) {}

    /**
     * Calculate stock value for a product in a warehouse.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @param  ValuationMethod  $method  Valuation method
     * @return array ['quantity' => string, 'value' => string, 'average_cost' => string]
     */
    public function calculateStockValue(
        string $productId,
        string $warehouseId,
        ValuationMethod $method
    ): array {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem || MathHelper::equals($stockItem->quantity, '0')) {
            return [
                'quantity' => '0',
                'value' => '0',
                'average_cost' => '0',
            ];
        }

        return match ($method) {
            ValuationMethod::FIFO => $this->calculateFifoValue($productId, $warehouseId, $stockItem->quantity),
            ValuationMethod::LIFO => $this->calculateLifoValue($productId, $warehouseId, $stockItem->quantity),
            ValuationMethod::WEIGHTED_AVERAGE => $this->calculateWeightedAverageValue($stockItem),
            ValuationMethod::STANDARD_COST => $this->calculateStandardCostValue($productId, $stockItem->quantity),
        };
    }

    /**
     * Calculate stock value for entire warehouse.
     *
     * @param  string  $warehouseId  Warehouse ID
     * @param  ValuationMethod  $method  Valuation method
     * @return array ['total_items' => int, 'total_quantity' => string, 'total_value' => string]
     */
    public function calculateWarehouseValue(string $warehouseId, ValuationMethod $method): array
    {
        $stockItems = $this->stockItemRepository->getByWarehouse($warehouseId, 1000);

        $totalItems = 0;
        $totalQuantity = '0';
        $totalValue = '0';

        foreach ($stockItems->items() as $stockItem) {
            if (MathHelper::greaterThan($stockItem->quantity, '0')) {
                $totalItems++;
                $totalQuantity = MathHelper::add($totalQuantity, $stockItem->quantity);

                $itemValue = $this->calculateStockValue(
                    $stockItem->product_id,
                    $warehouseId,
                    $method
                );

                $totalValue = MathHelper::add($totalValue, $itemValue['value']);
            }
        }

        return [
            'total_items' => $totalItems,
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
        ];
    }

    /**
     * Calculate stock value for a product across all warehouses.
     *
     * @param  string  $productId  Product ID
     * @param  ValuationMethod  $method  Valuation method
     * @return array ['total_warehouses' => int, 'total_quantity' => string, 'total_value' => string]
     */
    public function calculateProductValue(string $productId, ValuationMethod $method): array
    {
        $stockItems = $this->stockItemRepository->getByProduct($productId, 1000);

        $totalWarehouses = 0;
        $totalQuantity = '0';
        $totalValue = '0';

        foreach ($stockItems->items() as $stockItem) {
            if (MathHelper::greaterThan($stockItem->quantity, '0')) {
                $totalWarehouses++;
                $totalQuantity = MathHelper::add($totalQuantity, $stockItem->quantity);

                $itemValue = $this->calculateStockValue(
                    $productId,
                    $stockItem->warehouse_id,
                    $method
                );

                $totalValue = MathHelper::add($totalValue, $itemValue['value']);
            }
        }

        return [
            'total_warehouses' => $totalWarehouses,
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
        ];
    }

    /**
     * Update stock item average cost and fire value changed event.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @param  string  $newAverageCost  New average cost
     */
    public function updateStockValue(string $productId, string $warehouseId, string $newAverageCost): void
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouseOrFail($productId, $warehouseId);

        $oldValue = MathHelper::multiply($stockItem->quantity, $stockItem->average_cost);
        $newValue = MathHelper::multiply($stockItem->quantity, $newAverageCost);

        $this->stockItemRepository->update($stockItem->id, [
            'average_cost' => $newAverageCost,
        ]);

        // Fire event if value changed significantly
        if (! MathHelper::equals($oldValue, $newValue)) {
            event(new StockValueChanged(
                $productId,
                $warehouseId,
                $oldValue,
                $newValue
            ));
        }
    }

    /**
     * Calculate value using FIFO (First In First Out) method.
     */
    private function calculateFifoValue(string $productId, string $warehouseId, string $quantity): array
    {
        $receipts = $this->stockMovementRepository->getReceiptsForFifo(
            $productId,
            $warehouseId,
            100
        );

        $remainingQty = $quantity;
        $totalValue = '0';

        foreach ($receipts as $receipt) {
            if (MathHelper::lessThan($remainingQty, '0') || MathHelper::equals($remainingQty, '0')) {
                break;
            }

            $receiptQty = $receipt->quantity;
            $receiptCost = $receipt->cost ?? '0';

            if (MathHelper::greaterThan($receiptQty, $remainingQty)) {
                $receiptQty = $remainingQty;
            }

            $layerValue = MathHelper::multiply($receiptQty, $receiptCost);
            $totalValue = MathHelper::add($totalValue, $layerValue);
            $remainingQty = MathHelper::subtract($remainingQty, $receiptQty);
        }

        $avgCost = MathHelper::greaterThan($quantity, '0')
            ? MathHelper::divide($totalValue, $quantity)
            : '0';

        return [
            'quantity' => $quantity,
            'value' => $totalValue,
            'average_cost' => $avgCost,
        ];
    }

    /**
     * Calculate value using LIFO (Last In First Out) method.
     */
    private function calculateLifoValue(string $productId, string $warehouseId, string $quantity): array
    {
        $receipts = $this->stockMovementRepository->getReceiptsForLifo(
            $productId,
            $warehouseId,
            100
        );

        $remainingQty = $quantity;
        $totalValue = '0';

        foreach ($receipts as $receipt) {
            if (MathHelper::lessThan($remainingQty, '0') || MathHelper::equals($remainingQty, '0')) {
                break;
            }

            $receiptQty = $receipt->quantity;
            $receiptCost = $receipt->cost ?? '0';

            if (MathHelper::greaterThan($receiptQty, $remainingQty)) {
                $receiptQty = $remainingQty;
            }

            $layerValue = MathHelper::multiply($receiptQty, $receiptCost);
            $totalValue = MathHelper::add($totalValue, $layerValue);
            $remainingQty = MathHelper::subtract($remainingQty, $receiptQty);
        }

        $avgCost = MathHelper::greaterThan($quantity, '0')
            ? MathHelper::divide($totalValue, $quantity)
            : '0';

        return [
            'quantity' => $quantity,
            'value' => $totalValue,
            'average_cost' => $avgCost,
        ];
    }

    /**
     * Calculate value using Weighted Average method.
     *
     * @param  mixed  $stockItem  Stock item model with quantity and average_cost
     * @return array Array with quantity, value, and average_cost
     */
    private function calculateWeightedAverageValue($stockItem): array
    {
        $quantity = $stockItem->quantity;
        $averageCost = $stockItem->average_cost ?? '0';
        $totalValue = MathHelper::multiply($quantity, $averageCost);

        return [
            'quantity' => $quantity,
            'value' => $totalValue,
            'average_cost' => $averageCost,
        ];
    }

    /**
     * Calculate value using Standard Cost method.
     *
     * @param  string  $productId  Product identifier
     * @param  string  $quantity  Stock quantity as string for BCMath precision
     * @return array Array with quantity, value, and average_cost (standard cost)
     */
    private function calculateStandardCostValue(string $productId, string $quantity): array
    {
        // Fetch standard cost from product
        $product = app('Modules\Product\Repositories\ProductRepository')->findOrFail($productId);
        $standardCost = $product->standard_cost ?? $product->cost ?? '0';

        $totalValue = MathHelper::multiply($quantity, $standardCost);

        return [
            'quantity' => $quantity,
            'value' => $totalValue,
            'average_cost' => $standardCost,
        ];
    }

    /**
     * Recalculate and update average cost for a stock item.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @param  ValuationMethod  $method  Valuation method
     */
    public function recalculateAverageCost(
        string $productId,
        string $warehouseId,
        ValuationMethod $method
    ): void {
        $valuation = $this->calculateStockValue($productId, $warehouseId, $method);

        if (MathHelper::greaterThan($valuation['quantity'], '0')) {
            $this->updateStockValue($productId, $warehouseId, $valuation['average_cost']);
        }
    }

    /**
     * Get valuation summary for reporting.
     *
     * @param  string  $productId  Product ID
     * @param  string  $warehouseId  Warehouse ID
     * @return array Valuation summary with all methods
     */
    public function getValuationSummary(string $productId, string $warehouseId): array
    {
        $stockItem = $this->stockItemRepository->findByProductAndWarehouse($productId, $warehouseId);

        if (! $stockItem) {
            return [
                'quantity' => '0',
                'fifo' => '0',
                'lifo' => '0',
                'weighted_average' => '0',
                'standard_cost' => '0',
            ];
        }

        $fifo = $this->calculateStockValue($productId, $warehouseId, ValuationMethod::FIFO);
        $lifo = $this->calculateStockValue($productId, $warehouseId, ValuationMethod::LIFO);
        $weightedAvg = $this->calculateStockValue($productId, $warehouseId, ValuationMethod::WEIGHTED_AVERAGE);
        $standardCost = $this->calculateStockValue($productId, $warehouseId, ValuationMethod::STANDARD_COST);

        return [
            'quantity' => $stockItem->quantity,
            'fifo' => $fifo['value'],
            'lifo' => $lifo['value'],
            'weighted_average' => $weightedAvg['value'],
            'standard_cost' => $standardCost['value'],
        ];
    }
}

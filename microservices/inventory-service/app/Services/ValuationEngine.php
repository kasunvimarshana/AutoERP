<?php

namespace App\Services;

use App\Models\InventoryLedger;
use Enterprise\Core\Math\EnterpriseMath;

/**
 * ValuationEngine - Handles FEFO/FIFO/LIFO inventory valuation.
 * Essential for financial correctness and tax compliance.
 */
class ValuationEngine
{
    /**
     * Calculate the cost of goods sold (COGS) based on valuation method.
     */
    public function calculateCOGS(string $productId, string $quantity, string $method = 'FIFO'): string
    {
        $query = InventoryLedger::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->where('transaction_type', 'PURCHASE');

        switch ($method) {
            case 'FIFO':
                $lots = $query->orderBy('created_at', 'asc')->get();
                break;
            case 'LIFO':
                $lots = $query->orderBy('created_at', 'desc')->get();
                break;
            case 'FEFO':
                $lots = $query->whereNotNull('expiry_date')
                    ->orderBy('expiry_date', 'asc')
                    ->get();
                break;
            default:
                throw new \Exception("Unsupported valuation method: {$method}");
        }

        $remainingToValue = $quantity;
        $totalCost = '0.0000';

        foreach ($lots as $lot) {
            if ($remainingToValue <= 0) break;

            $toTake = min($lot->quantity, $remainingToValue);
            $totalCost = EnterpriseMath::add($totalCost, EnterpriseMath::mul((string)$toTake, $lot->unit_cost));
            $remainingToValue = EnterpriseMath::sub($remainingToValue, (string)$toTake);
        }

        if ($remainingToValue > 0) {
            throw new \Exception("Insufficient stock in ledger to calculate valuation for product {$productId}");
        }

        return $totalCost;
    }
}

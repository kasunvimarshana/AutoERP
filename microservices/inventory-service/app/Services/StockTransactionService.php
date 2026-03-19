<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\StockSummary;
use Illuminate\Support\Facades\DB;
use Enterprise\Core\Math\EnterpriseMath;

/**
 * StockTransactionService - Handles atomic, ledger-driven stock movements.
 * Implements pessimistic locking for deductions.
 */
class StockTransactionService
{
    /**
     * Record a stock movement.
     * Ensures atomicity between Ledger record and StockSummary update.
     */
    public function recordMovement(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Get or create StockSummary with pessimistic lock
            $summary = StockSummary::where([
                ['tenant_id', $data['tenant_id']],
                ['product_id', $data['product_id']],
                ['warehouse_id', $data['warehouse_id']],
                ['bin_id', $data['bin_id'] ?? null],
                ['lot_number', $data['lot_number'] ?? null],
            ])->lockForUpdate()->firstOrCreate([
                'tenant_id' => $data['tenant_id'],
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'bin_id' => $data['bin_id'] ?? null],
                ['quantity' => '0.0000']
            );

            // 2. Validate sufficient stock for deductions
            if ($data['quantity'] < 0 && EnterpriseMath::add($summary->quantity, $data['quantity']) < 0) {
                throw new \Exception("Insufficient stock for product {$data['product_id']} in warehouse {$data['warehouse_id']}");
            }

            // 3. Create Ledger Entry (Immutable)
            $ledger = InventoryLedger::create([
                'tenant_id' => $data['tenant_id'],
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'bin_id' => $data['bin_id'] ?? null,
                'lot_number' => $data['lot_number'] ?? null,
                'quantity' => $data['quantity'],
                'transaction_type' => $data['transaction_type'], // e.g., 'SALE', 'PURCHASE', 'ADJUSTMENT'
                'reference_id' => $data['reference_id'], // e.g., Order ID, Receipt ID
                'unit_cost' => $data['unit_cost'] ?? '0.0000',
                'valuation_method' => $data['valuation_method'] ?? 'FIFO',
                'created_at' => now(),
                'metadata' => $data['metadata'] ?? [],
            ]);

            // 4. Update StockSummary (Aggregated state)
            $summary->quantity = EnterpriseMath::add($summary->quantity, $data['quantity']);
            $summary->save();

            return $ledger;
        });
    }
}

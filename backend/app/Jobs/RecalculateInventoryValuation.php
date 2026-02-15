<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateInventoryValuation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $locationId = null,
        private readonly ?int $productId = null
    ) {}

    public function handle(): void
    {
        Log::info("Starting inventory valuation recalculation", [
            'tenant_id' => $this->tenantId,
            'location_id' => $this->locationId,
            'product_id' => $this->productId,
        ]);

        DB::transaction(function () {
            $query = DB::table('products')
                ->where('tenant_id', $this->tenantId);

            if ($this->productId) {
                $query->where('id', $this->productId);
            }

            $products = $query->get();

            foreach ($products as $product) {
                $this->recalculateProductValuation($product->id);
            }
        });

        Log::info("Inventory valuation recalculation completed");
    }

    private function recalculateProductValuation(int $productId): void
    {
        // Calculate FIFO valuation
        $stockLedgerQuery = DB::table('stock_ledgers')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $productId)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc');

        if ($this->locationId) {
            $stockLedgerQuery->where('location_id', $this->locationId);
        }

        $ledgers = $stockLedgerQuery->get();

        $totalQuantity = 0;
        $totalValue = 0;
        $availableStock = [];

        foreach ($ledgers as $ledger) {
            if ($ledger->transaction_type === 'in') {
                // Stock in - add to available stock
                $totalQuantity += $ledger->quantity;
                $totalValue += ($ledger->quantity * $ledger->unit_cost);
                
                $availableStock[] = [
                    'quantity' => $ledger->quantity,
                    'unit_cost' => $ledger->unit_cost,
                    'batch_number' => $ledger->batch_number,
                ];
            } elseif ($ledger->transaction_type === 'out') {
                // Stock out - consume from FIFO
                $remainingQty = abs($ledger->quantity);
                
                while ($remainingQty > 0 && count($availableStock) > 0) {
                    $batch = &$availableStock[0];
                    
                    if ($batch['quantity'] <= $remainingQty) {
                        // Consume entire batch
                        $totalQuantity -= $batch['quantity'];
                        $totalValue -= ($batch['quantity'] * $batch['unit_cost']);
                        $remainingQty -= $batch['quantity'];
                        array_shift($availableStock);
                    } else {
                        // Partial consumption
                        $batch['quantity'] -= $remainingQty;
                        $totalQuantity -= $remainingQty;
                        $totalValue -= ($remainingQty * $batch['unit_cost']);
                        $remainingQty = 0;
                    }
                }
            }
        }

        // Calculate average cost
        $averageCost = $totalQuantity > 0 ? ($totalValue / $totalQuantity) : 0;

        // Update product
        DB::table('products')
            ->where('id', $productId)
            ->update([
                'current_stock' => $totalQuantity,
                'stock_value' => $totalValue,
                'average_cost' => $averageCost,
                'updated_at' => now(),
            ]);

        Log::debug("Product valuation updated", [
            'product_id' => $productId,
            'quantity' => $totalQuantity,
            'value' => $totalValue,
            'average_cost' => $averageCost,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Inventory valuation recalculation failed", [
            'tenant_id' => $this->tenantId,
            'location_id' => $this->locationId,
            'product_id' => $this->productId,
            'error' => $exception->getMessage(),
        ]);
    }
}

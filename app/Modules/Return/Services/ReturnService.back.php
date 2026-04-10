<?php

namespace App\Services\Returns;

use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\InventoryTransaction;
use App\Services\Inventory\ValuationService;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    protected $valuationService;

    public function __construct(ValuationService $valuationService)
    {
        $this->valuationService = $valuationService;
    }

    public function processPurchaseReturn($purchaseReturnId)
    {
        $purchaseReturn = PurchaseReturn::with('items')->findOrFail($purchaseReturnId);
        DB::transaction(function () use ($purchaseReturn) {
            foreach ($purchaseReturn->items as $item) {
                if ($item->disposition === 'return_to_vendor') {
                    // Remove from inventory (consume layers)
                    $this->valuationService->setContext(
                        $item->product_id,
                        $purchaseReturn->warehouse_id,
                        $this->getValuationMethod($item->product_id, $purchaseReturn->warehouse_id)
                    )->consume($item->quantity, 'purchase_return', $purchaseReturn->id);
                    // Also create credit memo (external accounting)
                } elseif ($item->disposition === 'scrap') {
                    // Write off as loss
                    InventoryTransaction::create([
                        'transaction_id' => (string) \Str::uuid(),
                        'type' => 'adjustment',
                        'product_id' => $item->product_id,
                        'warehouse_id' => $purchaseReturn->warehouse_id,
                        'quantity' => $item->quantity,
                        'direction' => 'out',
                        'reference_type' => 'purchase_return_scrap',
                        'reference_id' => $purchaseReturn->id,
                    ]);
                }
            }
            $purchaseReturn->status = 'closed';
            $purchaseReturn->save();
        });
    }

    public function processSalesReturn($salesReturnId)
    {
        $salesReturn = SalesReturn::with('items')->findOrFail($salesReturnId);
        DB::transaction(function () use ($salesReturn) {
            foreach ($salesReturn->items as $item) {
                if ($item->restock_action === 'restock' && $item->condition === 'good') {
                    $unitCost = $this->getOriginalCost($item);
                    $this->valuationService->setContext(
                        $item->product_id,
                        $salesReturn->warehouse_id,
                        $this->getValuationMethod($item->product_id, $salesReturn->warehouse_id)
                    )->addLayer(
                        $item->batch_id,
                        $item->quantity,
                        $unitCost,
                        now(),
                        $item->batch?->expiry_date,
                        'sales_return',
                        $salesReturn->id
                    );

                    if ($item->serial_number) {
                        \App\Models\SerialNumber::where('serial', $item->serial_number)
                            ->update(['status' => 'available']);
                    }
                }
            }
            $salesReturn->status = 'closed';
            $salesReturn->save();
        });
    }

    protected function getValuationMethod($productId, $warehouseId)
    {
        $config = \App\Models\InventoryMethodsConfig::where('warehouse_id', $warehouseId)
            ->where(fn($q) => $q->where('product_id', $productId)->orWhereNull('product_id'))
            ->first();
        return $config->valuation_method ?? 'fifo';
    }

    protected function getOriginalCost($returnItem)
    {
        $transaction = InventoryTransaction::where('reference_type', 'sales_order')
            ->where('reference_id', $returnItem->salesReturn->sales_order_id)
            ->where('product_id', $returnItem->product_id)
            ->where('batch_id', $returnItem->batch_id)
            ->first();
        return $transaction->unit_cost ?? 0;
    }
}
<?php

namespace App\Services\Inventory;

use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Batch;
use App\Models\Lot;
use App\Models\SerialNumber;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    protected $valuationService;
    protected $allocationService;
    protected $uomService;

    public function __construct(
        ValuationService $valuationService,
        AllocationService $allocationService,
        \App\Services\UOM\UOMService $uomService
    ) {
        $this->valuationService = $valuationService;
        $this->allocationService = $allocationService;
        $this->uomService = $uomService;
    }

    public function receiveStock(
        $productId,
        $warehouseId,
        $quantity,
        $unitCost,
        $batchNumber = null,
        $serialNumbers = [],
        $locationId = null,
        $uomId = null,
        $referenceType = 'purchase_order',
        $referenceId = null
    ) {
        DB::transaction(function () use (
            $productId, $warehouseId, $quantity, $unitCost, $batchNumber,
            $serialNumbers, $locationId, $uomId, $referenceType, $referenceId
        ) {
            $product = Product::findOrFail($productId);
            $baseQuantity = $uomId ? $this->uomService->convert($productId, $uomId, $product->base_uom_id, $quantity) : $quantity;

            // Handle batch
            $batch = null;
            if ($product->is_lot_controlled && $batchNumber) {
                $batch = Batch::firstOrCreate(
                    ['batch_number' => $batchNumber, 'product_id' => $productId],
                    ['initial_quantity' => $baseQuantity, 'current_quantity' => $baseQuantity]
                );
            }

            // Handle lot if needed
            $lot = null;
            if ($product->is_lot_controlled && !$batchNumber && $batchNumber) { // simplified
                // lot creation logic
            }

            // Add valuation layer
            $this->valuationService->setContext($productId, $warehouseId, $this->getValuationMethod($productId, $warehouseId))
                ->addLayer(
                    $batch?->id,
                    $baseQuantity,
                    $unitCost,
                    now(),
                    $batch?->expiry_date,
                    $referenceType,
                    $referenceId
                );

            // Update stock
            $stock = InventoryStock::firstOrNew([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'batch_id' => $batch?->id,
                'lot_id' => $lot?->id,
            ]);
            $stock->quantity += $baseQuantity;
            $stock->unit_cost = $unitCost; // for weighted avg
            $stock->save();

            // Handle serials
            foreach ($serialNumbers as $serial) {
                SerialNumber::create([
                    'serial' => $serial,
                    'product_id' => $productId,
                    'batch_id' => $batch?->id,
                    'lot_id' => $lot?->id,
                    'status' => 'available',
                    'current_location_id' => $locationId,
                ]);
            }

            // Record transaction
            InventoryTransaction::create([
                'transaction_id' => (string) \Str::uuid(),
                'type' => 'purchase',
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'batch_id' => $batch?->id,
                'quantity' => $baseQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $baseQuantity * $unitCost,
                'direction' => 'in',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    public function issueStock($productId, $warehouseId, $quantity, $allocationStrategy = 'nearest_expiry', $referenceType = 'sales_order', $referenceId = null)
    {
        $allocations = $this->allocationService->allocate($productId, $warehouseId, $quantity, $allocationStrategy);

        DB::transaction(function () use ($allocations, $referenceType, $referenceId) {
            foreach ($allocations as $alloc) {
                $stock = InventoryStock::find($alloc['stock_id']);
                $stock->quantity -= $alloc['quantity'];
                $stock->reserved_quantity -= $alloc['quantity'];
                $stock->save();

                $this->valuationService->setContext($stock->product_id, $stock->warehouse_id, $this->getValuationMethod($stock->product_id, $stock->warehouse_id))
                    ->consume($alloc['quantity'], $referenceType, $referenceId);

                if ($stock->serial_id) {
                    SerialNumber::where('id', $stock->serial_id)->update(['status' => 'sold']);
                }
            }
        });
    }

    protected function getValuationMethod($productId, $warehouseId)
    {
        $config = \App\Models\InventoryMethodsConfig::where('warehouse_id', $warehouseId)
            ->where(fn($q) => $q->where('product_id', $productId)->orWhereNull('product_id'))
            ->first();
        return $config->valuation_method ?? config('inventory.default_valuation', 'fifo');
    }
}
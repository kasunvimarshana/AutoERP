<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryTransaction;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Inventory\Models\InventoryReservation;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    protected $valuationService;

    public function __construct(ValuationService $valuationService)
    {
        $this->valuationService = $valuationService;
    }

    /**
     * Process inbound transaction (purchase, return, adjustment).
     */
    public function inbound(Product $product, $quantity, $warehouseId, $locationId, $unitCost, $referenceType, $referenceId, $batchId = null, $serialNumbers = [])
    {
        DB::transaction(function () use ($product, $quantity, $warehouseId, $locationId, $unitCost, $referenceType, $referenceId, $batchId, $serialNumbers) {
            // Create transaction record
            $transaction = InventoryTransaction::create([
                'transaction_type' => $this->determineTransactionType($referenceType),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
                'quantity' => $quantity,
                'uom_id' => $product->uom_id, // Assuming base UOM
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'created_by' => auth()->id(),
            ]);

            // Update or create balance
            $balance = InventoryBalance::firstOrNew([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
            ]);
            $balance->quantity_on_hand += $quantity;
            $balance->last_movement_at = now();
            $balance->save();

            // Add valuation layer
            $this->valuationService->addLayer($transaction, $unitCost);

            // If serial numbers are provided, create them
            foreach ($serialNumbers as $serial) {
                \App\Modules\Traceability\Models\SerialNumber::create([
                    'serial' => $serial,
                    'product_id' => $product->id,
                    'batch_id' => $batchId,
                    'location_id' => $locationId,
                    'current_status' => 'in_stock',
                ]);
            }
        });
    }

    /**
     * Process outbound transaction (sales, return to vendor, adjustment).
     */
    public function outbound(Product $product, $quantity, $warehouseId, $locationId, $referenceType, $referenceId, $batchId = null, $serialNumbers = [])
    {
        DB::transaction(function () use ($product, $quantity, $warehouseId, $locationId, $referenceType, $referenceId, $batchId, $serialNumbers) {
            // Get cost using valuation method
            $unitCost = $this->valuationService->getCost($product, $quantity, $warehouseId);

            // Create transaction
            $transaction = InventoryTransaction::create([
                'transaction_type' => $this->determineTransactionType($referenceType),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
                'quantity' => -$quantity, // negative for outbound
                'uom_id' => $product->uom_id,
                'unit_cost' => $unitCost,
                'total_cost' => -$quantity * $unitCost,
                'created_by' => auth()->id(),
            ]);

            // Update balance
            $balance = InventoryBalance::where([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
            ])->first();
            if (!$balance) {
                throw new \Exception("No balance found for product {$product->id}");
            }
            $balance->quantity_on_hand -= $quantity;
            $balance->last_movement_at = now();
            $balance->save();

            // Consume valuation layers
            $this->valuationService->consumeLayers($product, $quantity, $warehouseId);

            // If serial numbers are provided, mark them as sold
            foreach ($serialNumbers as $serial) {
                $serialModel = \App\Modules\Traceability\Models\SerialNumber::where('serial', $serial)->first();
                if ($serialModel) {
                    $serialModel->current_status = 'sold';
                    $serialModel->last_movement_id = $transaction->id;
                    $serialModel->save();
                }
            }
        });
    }

    private function determineTransactionType($referenceType)
    {
        $map = [
            'purchase_order' => 'purchase',
            'sales_order' => 'sales',
            'adjustment' => 'adjustment',
            'return' => 'return',
            'transfer' => 'transfer',
            'scrap' => 'scrap',
        ];
        return $map[$referenceType] ?? 'adjustment';
    }
}
<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryTransaction;
use App\Modules\Inventory\Models\InventoryBalance;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    protected $valuationService;
    protected $allocationService;
    
    public function __construct(ValuationService $valuationService, AllocationService $allocationService)
    {
        $this->valuationService = $valuationService;
        $this->allocationService = $allocationService;
    }
    
    public function inbound($data)
    {
        return DB::transaction(function () use ($data) {
            $transaction = $this->createTransaction(array_merge($data, [
                'movement_type' => 'inbound',
                'transaction_number' => $this->generateTransactionNumber(),
                'transaction_date' => now(),
            ]));
            
            $this->updateBalance($transaction, 'inbound');
            $this->valuationService->addLayer($transaction, $data['unit_cost']);
            
            return $transaction;
        });
    }
    
    public function outbound($data)
    {
        return DB::transaction(function () use ($data) {
            $product = Product::find($data['product_id']);
            $unitCost = $this->valuationService->calculateCost($product, $data['quantity'], $data['from_warehouse_id']);
            
            $transaction = $this->createTransaction(array_merge($data, [
                'movement_type' => 'outbound',
                'transaction_number' => $this->generateTransactionNumber(),
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost * $data['quantity'],
                'transaction_date' => now(),
            ]));
            
            $this->updateBalance($transaction, 'outbound');
            $this->valuationService->consumeLayers($product, $data['quantity'], $data['from_warehouse_id']);
            
            return $transaction;
        });
    }
    
    public function transfer($data)
    {
        return DB::transaction(function () use ($data) {
            // Create outbound from source
            $outbound = $this->createTransaction(array_merge($data, [
                'movement_type' => 'outbound',
                'transaction_type' => 'transfer',
                'transaction_number' => $this->generateTransactionNumber(),
                'from_warehouse_id' => $data['source_warehouse_id'],
                'from_location_id' => $data['source_location_id'],
                'quantity' => -$data['quantity'],
                'transaction_date' => now(),
            ]));
            
            $this->updateBalance($outbound, 'outbound');
            
            // Create inbound to destination
            $inbound = $this->createTransaction(array_merge($data, [
                'movement_type' => 'inbound',
                'transaction_type' => 'transfer',
                'transaction_number' => $this->generateTransactionNumber(),
                'to_warehouse_id' => $data['destination_warehouse_id'],
                'to_location_id' => $data['destination_location_id'],
                'quantity' => $data['quantity'],
                'transaction_date' => now(),
            ]));
            
            $this->updateBalance($inbound, 'inbound');
            $this->valuationService->addLayer($inbound, $data['unit_cost']);
            
            return ['outbound' => $outbound, 'inbound' => $inbound];
        });
    }
    
    protected function createTransaction($data)
    {
        return InventoryTransaction::create($data);
    }
    
    protected function updateBalance($transaction, $direction)
    {
        $balance = InventoryBalance::firstOrNew([
            'product_id' => $transaction->product_id,
            'variant_id' => $transaction->variant_id,
            'warehouse_id' => $direction === 'inbound' ? $transaction->to_warehouse_id : $transaction->from_warehouse_id,
            'location_id' => $direction === 'inbound' ? $transaction->to_location_id : $transaction->from_location_id,
            'batch_id' => $transaction->batch_id,
        ]);
        
        if ($direction === 'inbound') {
            $balance->quantity_on_hand += abs($transaction->quantity);
            $balance->quantity_in_transit -= abs($transaction->quantity);
        } else {
            $balance->quantity_on_hand -= abs($transaction->quantity);
        }
        
        $balance->last_movement_at = now();
        $balance->save();
    }
    
    protected function generateTransactionNumber()
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}
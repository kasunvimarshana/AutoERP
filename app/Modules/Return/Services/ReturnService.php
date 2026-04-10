<?php

namespace App\Modules\Return\Services;

use App\Modules\Returns\Models\Return;
use App\Modules\Returns\Models\ReturnItem;
use App\Modules\Returns\Models\CreditMemo;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Traceability\Models\Batch;
use App\Modules\Traceability\Models\SerialNumber;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    protected $inventoryService;
    
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    
    public function processSalesReturn($data)
    {
        return DB::transaction(function () use ($data) {
            $return = Return::create([
                'return_number' => $this->generateReturnNumber(),
                'return_type' => 'sales',
                'reference_id' => $data['sales_order_id'],
                'reference_type' => 'sales_order',
                'customer_id' => $data['customer_id'],
                'return_date' => $data['return_date'],
                'status' => 'pending_approval',
                'restocking_fee' => $data['restocking_fee'] ?? 0,
                'shipping_fee' => $data['shipping_fee'] ?? 0,
                'total_amount' => 0,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);
            
            $totalCredit = 0;
            
            foreach ($data['items'] as $itemData) {
                $returnItem = $this->createReturnItem($return, $itemData);
                
                // Perform quality inspection
                $inspectionResult = $this->inspectItem($returnItem, $itemData);
                
                if ($inspectionResult['passed']) {
                    // Restock if condition is good
                    if ($itemData['disposition'] === 'restock') {
                        $this->restockItem($returnItem, $itemData);
                    }
                    
                    $creditAmount = $this->calculateCreditAmount($returnItem, $itemData);
                    $totalCredit += $creditAmount;
                    
                    $returnItem->update([
                        'credit_amount' => $creditAmount,
                        'quality_check_results' => $inspectionResult,
                    ]);
                } else {
                    $returnItem->update([
                        'disposition' => 'scrap',
                        'quality_check_results' => $inspectionResult,
                    ]);
                }
            }
            
            $return->update([
                'total_amount' => $totalCredit - $return->restocking_fee - $return->shipping_fee,
                'status' => 'approved',
            ]);
            
            // Create credit memo
            $creditMemo = $this->createCreditMemo($return, $totalCredit);
            $return->update(['credit_memo_id' => $creditMemo->id]);
            
            return $return;
        });
    }
    
    protected function createReturnItem($return, $data)
    {
        return ReturnItem::create([
            'return_id' => $return->id,
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'quantity' => $data['quantity'],
            'uom_id' => $data['uom_id'],
            'condition' => $data['condition'],
            'disposition' => $data['disposition'],
            'restock_quantity' => $data['restock_quantity'] ?? 0,
            'scrap_quantity' => $data['scrap_quantity'] ?? 0,
            'original_batch_id' => $data['original_batch_id'] ?? null,
            'original_serial_numbers' => $data['original_serial_numbers'] ?? null,
            'unit_cost' => $data['unit_cost'],
            'restocking_fee' => $data['restocking_fee'] ?? 0,
            'reason' => $data['reason'] ?? null,
        ]);
    }
    
    protected function inspectItem($returnItem, $data)
    {
        // Perform quality checks
        $passed = true;
        $issues = [];
        
        if ($returnItem->condition === 'damaged') {
            $passed = false;
            $issues[] = 'Item is damaged';
        }
        
        if ($returnItem->condition === 'defective') {
            $passed = false;
            $issues[] = 'Item is defective';
        }
        
        if ($returnItem->expiry_date && $returnItem->expiry_date < now()) {
            $passed = false;
            $issues[] = 'Item is expired';
        }
        
        return [
            'passed' => $passed,
            'issues' => $issues,
            'inspected_at' => now(),
            'inspected_by' => auth()->id(),
        ];
    }
    
    protected function restockItem($returnItem, $data)
    {
        // Create new batch if needed
        $batchId = $data['original_batch_id'];
        
        if ($data['create_new_batch'] ?? false) {
            $batch = Batch::create([
                'batch_number' => $this->generateBatchNumber(),
                'product_id' => $returnItem->product_id,
                'manufacturing_date' => now(),
                'expiry_date' => $data['new_expiry_date'] ?? null,
            ]);
            $batchId = $batch->id;
        }
        
        // Handle serial numbers
        $serialNumbers = [];
        if ($data['original_serial_numbers']) {
            foreach ($data['original_serial_numbers'] as $serial) {
                $serialModel = SerialNumber::where('serial_number', $serial)->first();
                if ($serialModel) {
                    $serialModel->update(['status' => 'in_stock']);
                    $serialNumbers[] = $serial;
                }
            }
        }
        
        // Restock via inventory service
        $this->inventoryService->inbound([
            'transaction_type' => 'return_in',
            'product_id' => $returnItem->product_id,
            'variant_id' => $returnItem->variant_id,
            'to_warehouse_id' => $data['warehouse_id'],
            'to_location_id' => $data['location_id'],
            'batch_id' => $batchId,
            'quantity' => $returnItem->restock_quantity,
            'uom_id' => $returnItem->uom_id,
            'unit_cost' => $returnItem->unit_cost,
            'reference_type' => 'return',
            'reference_id' => $returnItem->return_id,
        ]);
        
        $returnItem->update([
            'new_batch_id' => $batchId,
            'new_serial_numbers' => $serialNumbers,
        ]);
    }
    
    protected function calculateCreditAmount($returnItem, $data)
    {
        $baseAmount = $returnItem->quantity * $returnItem->unit_cost;
        
        // Apply restocking fee
        $restockingFee = $returnItem->restocking_fee;
        
        // Apply condition adjustment
        $conditionAdjustment = 0;
        if ($returnItem->condition === 'fair') {
            $conditionAdjustment = $baseAmount * 0.3; // 30% reduction
        } elseif ($returnItem->condition === 'damaged') {
            $conditionAdjustment = $baseAmount * 0.5; // 50% reduction
        }
        
        return $baseAmount - $restockingFee - $conditionAdjustment;
    }
    
    protected function createCreditMemo($return, $amount)
    {
        return CreditMemo::create([
            'memo_number' => $this->generateMemoNumber(),
            'return_id' => $return->id,
            'memo_type' => 'customer',
            'party_id' => $return->customer_id,
            'amount' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'status' => 'issued',
            'issue_date' => now(),
            'notes' => "Credit memo for return {$return->return_number}",
        ]);
    }
    
    protected function generateReturnNumber()
    {
        return 'RET-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
    
    protected function generateMemoNumber()
    {
        return 'CM-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
    
    protected function generateBatchNumber()
    {
        return 'BATCH-' . date('Ymd') . '-' . strtoupper(uniqid());
    }
}
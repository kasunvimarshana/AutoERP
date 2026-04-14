<?php

namespace App\Modules\Return\Services;

use App\Modules\Return\Models\Return;
use App\Modules\Return\Models\ReturnItem;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Purchase\Models\PurchaseOrder;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process a sales return.
     */
    public function processSalesReturn($data)
    {
        return DB::transaction(function () use ($data) {
            $return = Return::create([
                'return_type' => 'sales',
                'reference_type' => 'sales_order',
                'reference_id' => $data['sales_order_id'],
                'return_number' => $this->generateReturnNumber(),
                'customer_id' => $data['customer_id'],
                'return_date' => $data['return_date'],
                'status' => 'draft',
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0;
            foreach ($data['items'] as $itemData) {
                $item = $return->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'uom_id' => $itemData['uom_id'],
                    'condition' => $itemData['condition'],
                    'original_batch_id' => $itemData['original_batch_id'] ?? null,
                    'original_serial_numbers' => $itemData['original_serial_numbers'] ?? [],
                    'restock_quantity' => $itemData['restock_quantity'] ?? 0,
                    'scrap_quantity' => $itemData['scrap_quantity'] ?? 0,
                    'restocking_fee' => $itemData['restocking_fee'] ?? 0,
                    'unit_cost' => $itemData['unit_cost'],
                    'reason' => $itemData['reason'] ?? null,
                ]);

                // Handle restocking
                if ($item->restock_quantity > 0) {
                    $this->inventoryService->inbound(
                        $item->product,
                        $item->restock_quantity,
                        $data['warehouse_id'],
                        $data['location_id'],
                        $item->unit_cost, // cost used for restock
                        'return',
                        $return->id,
                        $item->original_batch_id, // could reuse or new
                        $item->original_serial_numbers // might restock same serials
                    );
                }

                // If damaged, handle scrap (e.g., mark as scrapped)
                if ($item->scrap_quantity > 0) {
                    // This might involve creating a scrap transaction
                    // For now, just mark serials as scrapped if any
                    foreach ($item->original_serial_numbers as $serial) {
                        $serialModel = \App\Modules\Traceability\Models\SerialNumber::where('serial', $serial)->first();
                        if ($serialModel) {
                            $serialModel->current_status = 'scrapped';
                            $serialModel->save();
                        }
                    }
                }

                $total += $item->quantity * $item->unit_cost - $item->restocking_fee;
            }

            $return->total_amount = $total;
            $return->save();

            // Generate credit memo
            $creditMemo = $return->creditMemo()->create([
                'memo_number' => $this->generateMemoNumber(),
                'reference_type' => 'return',
                'reference_id' => $return->id,
                'customer_id' => $data['customer_id'],
                'amount' => $total,
                'issued_at' => now(),
                'status' => 'issued',
            ]);

            $return->credit_memo_id = $creditMemo->id;
            $return->save();

            return $return;
        });
    }

    private function generateReturnNumber()
    {
        return 'RET-' . strtoupper(uniqid());
    }

    private function generateMemoNumber()
    {
        return 'CM-' . strtoupper(uniqid());
    }
}
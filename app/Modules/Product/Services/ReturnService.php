<?php

namespace App\Services\Returns;

class ReturnService
{
    public function processSalesReturn($returnId)
    {
        $return = SalesReturn::with('items')->findOrFail($returnId);

        DB::transaction(function () use ($return) {
            foreach ($return->items as $item) {
                if ($item->restock_action === 'restock' && $item->condition === 'good') {
                    // Add back to inventory as a new layer
                    $this->valuationService->setContext(
                        $item->product_id,
                        $return->warehouse_id,
                        $this->getValuationMethod($item->product_id, $return->warehouse_id)
                    )->addLayer(
                        $item->batch_id,
                        $item->quantity,
                        $this->getOriginalCost($item),
                        now(),
                        $item->batch?->expiry_date,
                        'sales_return',
                        $return->id
                    );

                    // Update serial status if applicable
                    if ($item->serial_number) {
                        SerialNumber::where('serial', $item->serial_number)
                            ->update(['status' => 'available']);
                    }
                }
            }
            $return->status = 'closed';
            $return->save();
        });
    }
}
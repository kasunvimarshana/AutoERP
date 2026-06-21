<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;
use Modules\Sales\Services\SalesDocumentCapabilityService;
use Modules\Sales\Services\SalesRelatedDocumentService;

final class SalesReturnResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'return_number' => $this->return_number,
            'return_date' => $this->return_date?->toDateString(),
            'return_type' => $this->enumValue($this->return_type),
            'status' => $this->enumValue($this->status),
            'status_label' => $this->statusLabel($this->status),
            'capabilities' => app(SalesDocumentCapabilityService::class)->forSalesReturn($this->resource),
            'related_documents' => app(SalesRelatedDocumentService::class)->forSalesReturn($this->resource),
            'customer' => $this->whenLoaded('customer', fn () => $this->summary($this->customer, ['customer_number', 'code', 'name', 'display_name'])),
            'warehouse' => $this->whenLoaded('warehouse', fn () => $this->summary($this->warehouse, ['code', 'name'])),
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->summary($this->warehouseLocation, ['code', 'name'])),
            'replacement_sales_order' => $this->whenLoaded('replacementSalesOrder', fn () => $this->summary($this->replacementSalesOrder, ['sales_order_number', 'status'])),
            'affects_inventory' => (bool) $this->affects_inventory,
            'affects_customer_balance' => (bool) $this->affects_customer_balance,
            'approval_required' => (bool) $this->approval_required,
            'reason' => $this->reason,
            'subtotal' => (string) $this->subtotal,
            'adjustment_return_total' => (string) $this->adjustment_return_total,
            'grand_total' => (string) $this->grand_total,
            'credit_note_id' => $this->credit_note_id,
            'credit_note' => $this->whenLoaded('creditNote', fn () => $this->summary($this->creditNote, ['credit_note_number', 'status', 'amount'])),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => SalesReturnLineResource::collection($this->lines)->resolve($request),
                [],
            ),
            'adjustment_allocations' => $this->whenLoaded(
                'adjustmentAllocations',
                fn () => SalesReturnAdjustmentAllocationResource::collection(
                    $this->adjustmentAllocations,
                )->resolve($request),
                [],
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

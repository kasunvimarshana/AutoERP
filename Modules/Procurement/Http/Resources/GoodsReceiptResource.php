<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Goods Receipt Resource
 *
 * Transforms goods receipt data for API responses.
 */
class GoodsReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'purchase_order_id' => $this->purchase_order_id,
            'received_date' => $this->received_date?->toIso8601String(),
            'received_by' => $this->received_by,
            'status' => $this->status,
            'matched_status' => $this->matched_status,
            'warehouse_id' => $this->warehouse_id,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

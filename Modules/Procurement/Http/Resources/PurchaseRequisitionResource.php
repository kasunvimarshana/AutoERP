<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Purchase Requisition Resource
 *
 * Transforms purchase requisition data for API responses.
 */
class PurchaseRequisitionResource extends JsonResource
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
            'requisition_number' => $this->requisition_number,
            'requester_id' => $this->requester_id,
            'department' => $this->department,
            'requested_date' => $this->requested_date?->toIso8601String(),
            'required_date' => $this->required_date?->toIso8601String(),
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'supplier_id' => $this->supplier_id,
            'currency' => $this->currency,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'rejection_reason' => $this->rejection_reason,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'lines' => PurchaseRequisitionLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

final class ReturnResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'tenant_id'             => $this->tenant_id,
            'org_unit_id'           => $this->org_unit_id,
            'reference_number'      => $this->reference_number,
            'type'                  => $this->type,
            'original_order_id'     => $this->original_order_id,
            'supplier_id'           => $this->supplier_id,
            'customer_id'           => $this->customer_id,
            'warehouse_id'          => $this->warehouse_id,
            'status'                => $this->status,
            'return_date'           => $this->return_date?->toDateString(),
            'reason'                => $this->reason,
            'subtotal'              => $this->subtotal,
            'tax_amount'            => $this->tax_amount,
            'total_amount'          => $this->total_amount,
            'restock_location_id'   => $this->restock_location_id,
            'credit_memo_number'    => $this->credit_memo_number,
            'credit_memo_issued_at' => $this->credit_memo_issued_at?->toIso8601String(),
            'fee_amount'            => $this->fee_amount,
            'fee_description'       => $this->fee_description,
            'notes'                 => $this->notes,
            'internal_notes'        => $this->internal_notes,
            'metadata'              => $this->metadata,
            'approved_by'           => $this->approved_by,
            'approved_at'           => $this->approved_at?->toIso8601String(),
            'processed_by'          => $this->processed_by,
            'processed_at'          => $this->processed_at?->toIso8601String(),
            'lines'                 => ReturnLineResource::collection($this->whenLoaded('lines')),
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}

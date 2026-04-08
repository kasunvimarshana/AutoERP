<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

final class OrderResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'org_unit_id'      => $this->org_unit_id,
            'reference_number' => $this->reference_number,
            'type'             => $this->type,
            'supplier_id'      => $this->supplier_id,
            'customer_id'      => $this->customer_id,
            'status'           => $this->status,
            'payment_status'   => $this->payment_status,
            'order_date'       => $this->order_date?->toDateString(),
            'expected_date'    => $this->expected_date?->toDateString(),
            'confirmed_at'     => $this->confirmed_at?->toIso8601String(),
            'completed_at'     => $this->completed_at?->toIso8601String(),
            'currency'         => $this->currency,
            'exchange_rate'    => $this->exchange_rate,
            'subtotal'         => $this->subtotal,
            'discount_amount'  => $this->discount_amount,
            'tax_amount'       => $this->tax_amount,
            'shipping_amount'  => $this->shipping_amount,
            'total_amount'     => $this->total_amount,
            'warehouse_id'     => $this->warehouse_id,
            'billing_address'  => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'notes'            => $this->notes,
            'internal_notes'   => $this->internal_notes,
            'metadata'         => $this->metadata,
            'created_by'       => $this->created_by,
            'lines'            => OrderLineResource::collection($this->whenLoaded('lines')),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}

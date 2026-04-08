<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class PurchaseOrderResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'order_date'      => $this->order_date?->toDateString(),
            'expected_date'   => $this->expected_date?->toDateString(),
            'received_date'   => $this->received_date?->toDateString(),
            'supplier_id'     => $this->supplier_id,
            'warehouse_id'    => $this->warehouse_id,
            'status'          => $this->status,
            'currency_code'   => $this->currency_code,
            'subtotal'        => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount'      => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'total_amount'    => $this->total_amount,
            'paid_amount'     => $this->paid_amount,
            'balance_due'     => $this->balance_due,
            'payment_status'  => $this->payment_status,
            'notes'           => $this->notes,
            'lines'           => PurchaseOrderLineResource::collection($this->whenLoaded('lines')),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}

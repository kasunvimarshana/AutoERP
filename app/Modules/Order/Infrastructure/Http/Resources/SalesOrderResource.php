<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class SalesOrderResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'order_number'    => $this->order_number,
            'order_date'      => $this->order_date?->toDateString(),
            'required_date'   => $this->required_date?->toDateString(),
            'customer_id'     => $this->customer_id,
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
            'payment_terms'   => $this->payment_terms,
            'notes'           => $this->notes,
            'lines'           => SalesOrderLineResource::collection($this->whenLoaded('lines')),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}

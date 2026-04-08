<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class ReturnOrderResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'return_number'     => $this->return_number,
            'return_date'       => $this->return_date?->toDateString(),
            'type'              => $this->type,
            'source_order_type' => $this->source_order_type,
            'source_order_id'   => $this->source_order_id,
            'status'            => $this->status,
            'subtotal'          => $this->subtotal,
            'tax_amount'        => $this->tax_amount,
            'restocking_fee'    => $this->restocking_fee,
            'refund_amount'     => $this->refund_amount,
            'reason'            => $this->reason,
            'resolution'        => $this->resolution,
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}

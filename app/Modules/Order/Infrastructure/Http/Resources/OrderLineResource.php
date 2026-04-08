<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

final class OrderLineResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'uuid'               => $this->uuid,
            'order_id'           => $this->order_id,
            'product_id'         => $this->product_id,
            'variant_id'         => $this->variant_id,
            'line_number'        => $this->line_number,
            'description'        => $this->description,
            'quantity'           => $this->quantity,
            'unit_of_measure'    => $this->unit_of_measure,
            'unit_price'         => $this->unit_price,
            'discount_percent'   => $this->discount_percent,
            'discount_amount'    => $this->discount_amount,
            'tax_rate'           => $this->tax_rate,
            'tax_amount'         => $this->tax_amount,
            'subtotal'           => $this->subtotal,
            'total'              => $this->total,
            'quantity_received'  => $this->quantity_received,
            'quantity_delivered' => $this->quantity_delivered,
            'batch_lot_id'       => $this->batch_lot_id,
            'serial_number_id'   => $this->serial_number_id,
            'notes'              => $this->notes,
            'metadata'           => $this->metadata,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}

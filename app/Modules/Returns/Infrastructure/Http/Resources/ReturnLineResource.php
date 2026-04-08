<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

final class ReturnLineResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'uuid'                 => $this->uuid,
            'return_id'            => $this->return_id,
            'order_line_id'        => $this->order_line_id,
            'product_id'           => $this->product_id,
            'variant_id'           => $this->variant_id,
            'batch_lot_id'         => $this->batch_lot_id,
            'serial_number_id'     => $this->serial_number_id,
            'quantity_requested'   => $this->quantity_requested,
            'quantity_approved'    => $this->quantity_approved,
            'quantity_received'    => $this->quantity_received,
            'unit_price'           => $this->unit_price,
            'subtotal'             => $this->subtotal,
            'quality_check_result' => $this->quality_check_result,
            'quality_notes'        => $this->quality_notes,
            'condition_notes'      => $this->condition_notes,
            'restock_action'       => $this->restock_action,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}

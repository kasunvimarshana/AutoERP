<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class CycleCountLineResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'product_id'       => $this->product_id,
            'variant_id'       => $this->variant_id,
            'batch_lot_id'     => $this->batch_lot_id,
            'system_quantity'  => $this->system_quantity,
            'counted_quantity' => $this->counted_quantity,
            'variance'         => $this->variance,
            'status'           => $this->status,
            'notes'            => $this->notes,
        ];
    }
}

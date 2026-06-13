<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Http\Resources\Concerns\FormatsSalesResources;

final class SalesReturnableDeliveryLineResource extends JsonResource
{
    use FormatsSalesResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'source_line_type' => 'sales_delivery_line',
            'source_line_id' => (int) $this->getKey(),
            'item' => $this->relationLoaded('item')
                ? $this->summary($this->item, ['code', 'name'])
                : null,
            'uom' => $this->relationLoaded('uom')
                ? $this->summary($this->uom, ['code', 'name'])
                : null,
            'returnable_quantity' => (string) $this->returnable_quantity,
            'unit_price' => (string) $this->unit_price,
        ];
    }
}

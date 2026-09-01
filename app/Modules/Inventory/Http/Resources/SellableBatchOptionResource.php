<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellableBatchOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'item_id' => (int) $this->item_id,
            'code' => $this->item?->code,
            'name' => $this->item?->name,
            'item_type' => $this->item?->item_type instanceof \BackedEnum ? $this->item->item_type->value : (string) $this->item?->item_type,
            'tracking_type' => $this->item?->tracking_type instanceof \BackedEnum ? $this->item->tracking_type->value : (string) $this->item?->tracking_type,
            'is_stockable' => true,
            'item_variant_id' => $this->item_variant_id,
            'base_uom' => $this->item?->baseUom === null ? null : [
                'id' => (int) $this->item->baseUom->getKey(),
                'code' => $this->item->baseUom->code,
                'name' => $this->item->baseUom->name,
            ],
            'batch' => [
                'id' => (int) $this->getKey(),
                'code' => $this->batch_number,
                'name' => $this->lot_number ?: $this->batch_number,
                'batch_number' => $this->batch_number,
                'lot_number' => $this->lot_number,
                'expiry_date' => $this->expiry_date?->toDateString(),
            ],
            'batch_price_revision_id' => $this->getAttribute('batch_price_revision_id'),
            'resolved_service_unit_price' => (string) $this->getAttribute('resolved_service_unit_price'),
            'resolved_purchase_unit_price' => '0.000000',
            'available_stock_quantity' => (string) $this->getAttribute('available_stock_quantity'),
            'price_source' => $this->getAttribute('price_source'),
        ];
    }
}

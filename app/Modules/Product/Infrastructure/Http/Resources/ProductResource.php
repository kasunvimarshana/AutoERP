<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class ProductResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'category_id'      => $this->category_id,
            'sku'              => $this->sku,
            'barcode'          => $this->barcode,
            'name'             => $this->name,
            'description'      => $this->description,
            'type'             => $this->type,
            'status'           => $this->status,
            'unit_of_measure'  => $this->unit_of_measure,
            'cost_price'       => $this->cost_price,
            'selling_price'    => $this->selling_price,
            'currency_code'    => $this->currency_code,
            'tax_rate'         => $this->tax_rate,
            'is_taxable'       => $this->is_taxable,
            'is_trackable'     => $this->is_trackable,
            'is_purchasable'   => $this->is_purchasable,
            'is_sellable'      => $this->is_sellable,
            'min_stock_level'  => $this->min_stock_level,
            'max_stock_level'  => $this->max_stock_level,
            'reorder_point'    => $this->reorder_point,
            'reorder_quantity' => $this->reorder_quantity,
            'lead_time_days'   => $this->lead_time_days,
            'image_path'       => $this->image_path,
            'metadata'         => $this->metadata,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}

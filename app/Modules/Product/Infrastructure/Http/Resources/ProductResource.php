<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->getId(),
            'tenant_id'         => $this->getTenantId(),
            'sku'               => $this->getSku()->value(),
            'name'              => $this->getName(),
            'description'       => $this->getDescription(),
            'price'             => [
                'amount'   => $this->getPrice()->getAmount(),
                'currency' => $this->getPrice()->getCurrency(),
            ],
            'category'          => $this->getCategory(),
            'status'            => $this->getStatus(),
            'type'              => $this->getType()->value(),
            'units_of_measure'  => array_map(
                fn ($uom) => $uom->toArray(),
                $this->getUnitsOfMeasure()
            ),
            'attributes'        => $this->getAttributes(),
            'metadata'          => $this->getMetadata(),
            'product_attributes' => array_map(
                fn ($attr) => $attr->toArray(),
                $this->getProductAttributes()
            ),
            'images'            => ProductImageResource::collection($this->getImages()),
            'variations'        => ProductVariationResource::collection($this->getVariations()),
            'combo_items'       => ComboItemResource::collection($this->getComboItems()),
            'created_at'        => $this->getCreatedAt()->format('c'),
            'updated_at'        => $this->getUpdatedAt()->format('c'),
        ];
    }
}


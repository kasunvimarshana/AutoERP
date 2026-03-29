<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Product\Domain\Entities\ProductVariation
 */
class ProductVariationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'product_id'       => $this->getProductId(),
            'tenant_id'        => $this->getTenantId(),
            'sku'              => $this->getSku()->value(),
            'name'             => $this->getName(),
            'price'            => [
                'amount'   => $this->getPrice()->getAmount(),
                'currency' => $this->getPrice()->getCurrency(),
            ],
            'attribute_values' => $this->getAttributeValues(),
            'status'           => $this->getStatus(),
            'sort_order'       => $this->getSortOrder(),
            'metadata'         => $this->getMetadata(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceListItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->getId(),
            'tenant_id'       => $this->getTenantId(),
            'price_list_id'   => $this->getPriceListId(),
            'product_id'      => $this->getProductId(),
            'variation_id'    => $this->getVariationId(),
            'unit_price'      => $this->getUnitPrice(),
            'min_quantity'    => $this->getMinQuantity(),
            'max_quantity'    => $this->getMaxQuantity(),
            'discount_percent'=> $this->getDiscountPercent(),
            'markup_percent'  => $this->getMarkupPercent(),
            'currency_code'   => $this->getCurrencyCode(),
            'uom_code'        => $this->getUomCode(),
            'is_active'       => $this->isActive(),
            'metadata'        => $this->getMetadata()->toArray(),
            'created_at'      => $this->getCreatedAt()->format('c'),
            'updated_at'      => $this->getUpdatedAt()->format('c'),
        ];
    }
}

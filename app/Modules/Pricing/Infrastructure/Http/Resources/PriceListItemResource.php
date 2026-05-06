<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'price_list_id' => $this->getPriceListId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'uom_id' => $this->getUomId(),
            'min_quantity' => $this->getMinQuantity(),
            'price' => $this->getPrice(),
            'discount_pct' => $this->getDiscountPct(),
            'valid_from' => $this->getValidFrom()?->format('Y-m-d'),
            'valid_to' => $this->getValidTo()?->format('Y-m-d'),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Product\Domain\Entities\ComboItem
 */
class ComboItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $priceOverride = $this->getPriceOverride();

        return [
            'id'                   => $this->getId(),
            'product_id'           => $this->getProductId(),
            'tenant_id'            => $this->getTenantId(),
            'component_product_id' => $this->getComponentProductId(),
            'quantity'             => $this->getQuantity(),
            'price_override'       => $priceOverride ? [
                'amount'   => $priceOverride->getAmount(),
                'currency' => $priceOverride->getCurrency(),
            ] : null,
            'sort_order'           => $this->getSortOrder(),
            'metadata'             => $this->getMetadata(),
            'created_at'           => $this->getCreatedAt()->format('c'),
            'updated_at'           => $this->getUpdatedAt()->format('c'),
        ];
    }
}

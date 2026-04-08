<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryValuationLayerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'product_id'       => $this->getProductId(),
            'variation_id'     => $this->getVariationId(),
            'batch_id'         => $this->getBatchId(),
            'location_id'      => $this->getLocationId(),
            'layer_date'       => $this->getLayerDate()->format('Y-m-d'),
            'qty_in'           => $this->getQtyIn(),
            'qty_remaining'    => $this->getQtyRemaining(),
            'unit_cost'        => $this->getUnitCost(),
            'total_value'      => $this->getTotalValue(),
            'currency'         => $this->getCurrency(),
            'valuation_method' => $this->getValuationMethod(),
            'reference_type'   => $this->getReferenceType(),
            'reference_id'     => $this->getReferenceId(),
            'is_closed'        => $this->isClosed(),
            'metadata'         => $this->getMetadata()->toArray(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}

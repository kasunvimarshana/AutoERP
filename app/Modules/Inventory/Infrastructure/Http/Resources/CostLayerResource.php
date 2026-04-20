<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostLayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'product_id' => $this->resource->getProductId(),
            'variant_id' => $this->resource->getVariantId(),
            'batch_id' => $this->resource->getBatchId(),
            'location_id' => $this->resource->getLocationId(),
            'valuation_method' => $this->resource->getValuationMethod(),
            'layer_date' => $this->resource->getLayerDate(),
            'quantity_in' => $this->resource->getQuantityIn(),
            'quantity_remaining' => $this->resource->getQuantityRemaining(),
            'unit_cost' => $this->resource->getUnitCost(),
            'reference_type' => $this->resource->getReferenceType(),
            'reference_id' => $this->resource->getReferenceId(),
            'is_closed' => $this->resource->isClosed(),
        ];
    }
}

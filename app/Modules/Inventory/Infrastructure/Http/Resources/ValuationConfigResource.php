<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValuationConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'org_unit_id' => $this->resource->getOrgUnitId(),
            'warehouse_id' => $this->resource->getWarehouseId(),
            'product_id' => $this->resource->getProductId(),
            'transaction_type' => $this->resource->getTransactionType(),
            'valuation_method' => $this->resource->getValuationMethod(),
            'allocation_strategy' => $this->resource->getAllocationStrategy(),
            'is_active' => $this->resource->isActive(),
            'metadata' => $this->resource->getMetadata(),
        ];
    }
}

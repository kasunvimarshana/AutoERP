<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'product_id' => $this->getProductId(),
            'name' => $this->getName(),
            'sku' => $this->getSku(),
            'is_default' => $this->isDefault(),
            'is_active' => $this->isActive(),
            'purchase_price' => $this->getPurchasePrice(),
            'sales_price' => $this->getSalesPrice(),
            'metadata' => $this->getMetadata(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}

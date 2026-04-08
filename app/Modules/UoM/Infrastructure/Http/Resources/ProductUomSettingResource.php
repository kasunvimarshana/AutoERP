<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductUomSettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->getId(),
            'tenant_id'        => $this->getTenantId(),
            'product_id'       => $this->getProductId(),
            'base_uom_id'      => $this->getBaseUomId(),
            'purchase_uom_id'  => $this->getPurchaseUomId(),
            'sales_uom_id'     => $this->getSalesUomId(),
            'inventory_uom_id' => $this->getInventoryUomId(),
            'purchase_factor'  => $this->getPurchaseFactor(),
            'sales_factor'     => $this->getSalesFactor(),
            'inventory_factor' => $this->getInventoryFactor(),
            'is_active'        => $this->isActive(),
            'created_at'       => $this->getCreatedAt()->format('c'),
            'updated_at'       => $this->getUpdatedAt()->format('c'),
        ];
    }
}

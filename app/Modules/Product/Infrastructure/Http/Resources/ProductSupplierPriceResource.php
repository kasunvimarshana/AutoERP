<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSupplierPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'supplier_id' => $this->getSupplierId(),
            'currency_id' => $this->getCurrencyId(),
            'uom_id' => $this->getUomId(),
            'min_order_quantity' => $this->getMinOrderQuantity(),
            'unit_price' => $this->getUnitPrice(),
            'discount_percent' => $this->getDiscountPercent(),
            'lead_time_days' => $this->getLeadTimeDays(),
            'is_preferred' => $this->isPreferred(),
            'is_active' => $this->isActive(),
            'effective_from' => $this->getEffectiveFrom()?->format('Y-m-d'),
            'effective_to' => $this->getEffectiveTo()?->format('Y-m-d'),
            'notes' => $this->getNotes(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()?->toISOString(),
            'updated_at' => $this->getUpdatedAt()?->toISOString(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'supplier_id' => $this->getSupplierId(),
            'product_id' => $this->getProductId(),
            'variant_id' => $this->getVariantId(),
            'supplier_sku' => $this->getSupplierSku(),
            'lead_time_days' => $this->getLeadTimeDays(),
            'min_order_qty' => $this->getMinOrderQty(),
            'is_preferred' => $this->isPreferred(),
            'last_purchase_price' => $this->getLastPurchasePrice(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}

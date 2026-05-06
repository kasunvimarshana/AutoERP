<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\PurchaseOrderLine;

class PurchaseOrderLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PurchaseOrderLine $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'purchase_order_id' => $entity->getPurchaseOrderId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'description' => $entity->getDescription(),
            'uom_id' => $entity->getUomId(),
            'ordered_qty' => $entity->getOrderedQty(),
            'received_qty' => $entity->getReceivedQty(),
            'unit_price' => $entity->getUnitPrice(),
            'discount_pct' => $entity->getDiscountPct(),
            'tax_group_id' => $entity->getTaxGroupId(),
            'account_id' => $entity->getAccountId(),
                        'line_total' => $entity->getLineTotal(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}

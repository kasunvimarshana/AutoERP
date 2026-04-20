<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\GrnLine;

class GrnLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var GrnLine $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'grn_header_id' => $entity->getGrnHeaderId(),
            'purchase_order_line_id' => $entity->getPurchaseOrderLineId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'batch_id' => $entity->getBatchId(),
            'serial_id' => $entity->getSerialId(),
            'location_id' => $entity->getLocationId(),
            'uom_id' => $entity->getUomId(),
            'expected_qty' => $entity->getExpectedQty(),
            'received_qty' => $entity->getReceivedQty(),
            'rejected_qty' => $entity->getRejectedQty(),
            'unit_cost' => $entity->getUnitCost(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}

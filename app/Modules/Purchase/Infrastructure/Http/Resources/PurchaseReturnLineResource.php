<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\PurchaseReturnLine;

class PurchaseReturnLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PurchaseReturnLine $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'purchase_return_id' => $entity->getPurchaseReturnId(),
            'original_grn_line_id' => $entity->getOriginalGrnLineId(),
            'product_id' => $entity->getProductId(),
            'variant_id' => $entity->getVariantId(),
            'batch_id' => $entity->getBatchId(),
            'serial_id' => $entity->getSerialId(),
            'from_location_id' => $entity->getFromLocationId(),
            'uom_id' => $entity->getUomId(),
            'return_qty' => $entity->getReturnQty(),
            'unit_cost' => $entity->getUnitCost(),
            'condition' => $entity->getCondition(),
            'disposition' => $entity->getDisposition(),
            'restocking_fee' => $entity->getRestockingFee(),
            'quality_check_notes' => $entity->getQualityCheckNotes(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}

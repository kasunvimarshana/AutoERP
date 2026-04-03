<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->getId(),
            'tenant_id'              => $this->getTenantId(),
            'goods_receipt_id'       => $this->getGoodsReceiptId(),
            'line_number'            => $this->getLineNumber(),
            'purchase_order_line_id' => $this->getPurchaseOrderLineId(),
            'product_id'             => $this->getProductId(),
            'variation_id'           => $this->getVariationId(),
            'batch_id'               => $this->getBatchId(),
            'serial_number'          => $this->getSerialNumber(),
            'uom_id'                 => $this->getUomId(),
            'quantity_expected'      => $this->getQuantityExpected(),
            'quantity_received'      => $this->getQuantityReceived(),
            'quantity_accepted'      => $this->getQuantityAccepted(),
            'quantity_rejected'      => $this->getQuantityRejected(),
            'unit_cost'              => $this->getUnitCost(),
            'condition'              => $this->getCondition(),
            'notes'                  => $this->getNotes(),
            'metadata'               => $this->getMetadata()->toArray(),
            'status'                 => $this->getStatus(),
            'putaway_location_id'    => $this->getPutawayLocationId(),
            'created_at'             => $this->getCreatedAt()->format('c'),
            'updated_at'             => $this->getUpdatedAt()->format('c'),
        ];
    }
}
